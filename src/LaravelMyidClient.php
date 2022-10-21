<?php

namespace Uzbek\LaravelMyidClient;

use Uzbek\LaravelMyidClient\Exceptions\{MyIDCacheNotExists, MyIDNotAuthorizedException};
use Uzbek\LaravelMyidClient\Services\{MyIDCompareFace, MyIDInPlace, MyIDRedirect, MyIDSdk, MyIDWebsite, Service};

class LaravelMyidClient extends Service
{
    const AUTH_CODE_GRANT_TYPE = 'authorization_code';
    const PASSWORD_GRANT_TYPE = 'password';

    const CACHE_PREFIX = 'myid_';
    const AUTH_CODE_TOKEN = self::CACHE_PREFIX . 'auth_code_token';
    const PASSWORD_TOKEN = self::CACHE_PREFIX . 'password_token';
    const AUTH_CODE_REF_TOKEN = self::CACHE_PREFIX . 'auth_code_refresh_token';
    const PASSWORD_REF_TOKEN = self::CACHE_PREFIX . 'password_refresh_token';

    const TOKEN_REFRESH_URL = 'oauth2/refresh-token';
    const ACCESS_TOKEN_URL = 'oauth2/access-token';

    protected ?string $auth_code_token = null;
    protected ?string $password_token = null;

    public function sdk(string $auth_code): MyIDSdk
    {
        $this->loginByAuthCode($auth_code);

        if ($this->auth_code_token === null) {
            throw new MyIDNotAuthorizedException;
        }

        return new MyIDSdk();
    }

    protected function loginByAuthCode(string $auth_code): void
    {
        if (cache()->has(self::AUTH_CODE_TOKEN . $auth_code)) {
            $this->auth_code_token = cache()->get(self::AUTH_CODE_TOKEN . $auth_code);
        } elseif (cache()->has(self::AUTH_CODE_REF_TOKEN . $auth_code)) {
            $this->refreshToken($auth_code);
        } else {
            $this->getAccessToken($auth_code);
        }
    }

    protected function refreshToken(string $auth_code)
    {
        $ref_cache_key = self::AUTH_CODE_REF_TOKEN . $auth_code;
        throw_if(!cache()->has($ref_cache_key), new MyIDCacheNotExists());

        $res = $this->client->asJson()->withToken($this->password_token)->post(self::TOKEN_REFRESH_URL, [
            'client_id' => $this->client_id,
            'refresh_token' => cache($ref_cache_key)
        ])->throw(fn($r, $e) => self::catchHttpRequestError($r, $e))->json();

        throw_if($res['access_token'] === null, new MyIDCacheNotExists());

        $this->auth_code_token = $res['access_token'];
        cache()->put(self::AUTH_CODE_TOKEN . $auth_code, $res['access_token'], $res['expires_in'] - 10);
    }

    protected function getAccessToken(string $auth_code)
    {
        $res = $this->client->asJson()->withToken($this->password_token)->post(self::ACCESS_TOKEN_URL, [
            'grant_type' => self::AUTH_CODE_GRANT_TYPE,
            'code' => $auth_code,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
        ])->throw(fn($r, $e) => self::catchHttpRequestError($r, $e))->json();

        throw_if($res['access_token'] === null, new MyIDCacheNotExists());

        $this->auth_code_token = $res['access_token'];
        cache()->put(self::AUTH_CODE_TOKEN . $auth_code, $res['access_token'], $res['expires_in'] - 10);
        cache()->put(self::AUTH_CODE_REF_TOKEN . $auth_code, $res['refresh_token'], $res['expires_in'] - 10);
    }

    public function sdkExternal(string $external_id)
    {
        $this->loginByPassword();

        if ($this->password_token === null) {
            throw new MyIDNotAuthorizedException;
        }

        return new MyIDSdk($external_id);
    }

    protected function loginByPassword(): void
    {
        if (cache()->has(self::PASSWORD_TOKEN)) {
            $this->password_token = cache()->get(self::PASSWORD_TOKEN);
        } elseif (cache()->has(self::PASSWORD_REF_TOKEN)) {
            $this->refreshPasswordToken();
        } else {
            $this->getPasswordToken();
        }
    }

    protected function refreshPasswordToken()
    {
        $request = $this->sendRequest('post', 'oauth2/access-token', [
            'refresh_token' => cache()->get(self::PASSWORD_REF_TOKEN),
            'client_id' => $this->config['client_id'],
        ], ['Accept' => 'application/json']);

        if ($request['access_token'] !== null && $request['refresh_token'] !== null) {
            $this->password_token = $request['access_token'];
            $this->putCachePasswordAccessRefreshToken($request['access_token'], $request['refresh_token'], $request['expires_in']);
        }

        return $request;
    }

    protected function putCachePasswordAccessRefreshToken(string $access_token, string $refresh_token, int $expires_in): void
    {
        cache()->put(self::PASSWORD_TOKEN, $access_token, $expires_in - 10);
        cache()->put(self::PASSWORD_REF_TOKEN, $refresh_token, $expires_in - 10);
    }

    protected function getPasswordToken()
    {
        $request = $this->sendRequest('post', 'oauth2/access-token', [
            'grant_type' => self::PASSWORD_GRANT_TYPE,
            'username' => $this->config['username'],
            'password' => $this->config['password'],
            'client_id' => $this->config['client_id'],
        ])->asForm();

        if ($request['access_token'] !== null && $request['refresh_token'] !== null) {
            $this->password_token = $request['access_token'];
            $this->putCachePasswordAccessRefreshToken($request['access_token'], $request['refresh_token'], $request['expires_in']);
        }

        return $request;
    }

    public function inPlace(): MyIDInPlace
    {
        return new MyIDInPlace();
    }

    public function website(): MyIDWebsite
    {
        return new MyIDWebsite();
    }

    public function redirect(): MyIDRedirect
    {
        return new MyIDRedirect();
    }

    public function compareFace(): MyIDCompareFace
    {
        return new MyIDCompareFace();
    }
}
