<?php

namespace Uzbek\LaravelMyidClient;

use Uzbek\LaravelMyidClient\Exceptions\MyIDCacheNotExists;
use Uzbek\LaravelMyidClient\Exceptions\MyIDNotAuthorizedException;
use Uzbek\LaravelMyidClient\Services\MyIDCompareFace;
use Uzbek\LaravelMyidClient\Services\MyIDInPlace;
use Uzbek\LaravelMyidClient\Services\MyIDRedirect;
use Uzbek\LaravelMyidClient\Services\MyIDSdk;
use Uzbek\LaravelMyidClient\Services\MyIDWebsite;
use Uzbek\LaravelMyidClient\Services\Service;

class LaravelMyidClient extends Service
{
    const AUTH_CODE_GRANT_TYPE = 'authorization_code';

    const PASSWORD_GRANT_TYPE = 'password';

    const CACHE_PREFIX = 'myid_';

    const AUTH_CODE_TOKEN = self::CACHE_PREFIX.'auth_code_token';

    const PASSWORD_TOKEN = self::CACHE_PREFIX.'password_token';

    const AUTH_CODE_REF_TOKEN = self::CACHE_PREFIX.'auth_code_refresh_token';

    const PASSWORD_REF_TOKEN = self::CACHE_PREFIX.'password_refresh_token';

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

        return new MyIDSdk($this->auth_code_token);
    }

    public function sdkExternal()
    {
        $this->loginByPassword();

        if ($this->password_token === null) {
            throw new MyIDNotAuthorizedException;
        }

        return new MyIDSdk($this->password_token);
    }

    protected function loginByAuthCode(string $auth_code): void
    {
        if (cache()->has(self::AUTH_CODE_TOKEN.$auth_code)) {
            $this->auth_code_token = cache()->get(self::AUTH_CODE_TOKEN.$auth_code);
        } elseif (cache()->has(self::AUTH_CODE_REF_TOKEN.$auth_code)) {
            $this->refreshToken($auth_code);
        } else {
            $this->getAccessToken($auth_code);
        }
    }

    protected function refreshToken(string $auth_code)
    {
        $ref_cache_key = self::AUTH_CODE_REF_TOKEN.$auth_code;
        throw_if(! cache()->has($ref_cache_key), new MyIDCacheNotExists());

        $res = $this->client->asJson()->post(self::TOKEN_REFRESH_URL, [
            'client_id' => $this->client_id,
            'refresh_token' => cache($ref_cache_key),
        ])->throw(fn ($r, $e) => self::catchHttpRequestError($r, $e))->json();

        throw_if($res['access_token'] === null, new MyIDCacheNotExists());

        $this->auth_code_token = $res['access_token'];
        cache()->put(self::AUTH_CODE_TOKEN.$auth_code, $res['access_token'], $res['expires_in'] - 10);
    }

    protected function getAccessToken(string $auth_code)
    {
        $res = $this->client->asForm()->post(self::ACCESS_TOKEN_URL, [
            'grant_type' => self::AUTH_CODE_GRANT_TYPE,
            'code' => $auth_code,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
        ])->throw(fn ($r, $e) => self::catchHttpRequestError($r, $e))->json();

        throw_if($res['access_token'] === null, new MyIDCacheNotExists());

        $this->auth_code_token = $res['access_token'];
        cache()->put(self::AUTH_CODE_TOKEN.$auth_code, $res['access_token'], $res['expires_in'] - 10);
        cache()->put(self::AUTH_CODE_REF_TOKEN.$auth_code, $res['refresh_token'], $res['expires_in'] - 10);
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
        $ref_cache_key = self::PASSWORD_REF_TOKEN;
        throw_if(! cache()->has($ref_cache_key), new MyIDCacheNotExists());

        $res = $this->client->asJson()->post(self::TOKEN_REFRESH_URL, [
            'client_id' => $this->client_id,
            'refresh_token' => cache($ref_cache_key),
        ])->throw(fn ($r, $e) => self::catchHttpRequestError($r, $e))->json();

        throw_if($res['access_token'] === null, new MyIDCacheNotExists());

        $this->auth_code_token = $res['access_token'];
        cache()->put(self::PASSWORD_TOKEN, $res['access_token'], $res['expires_in'] - 10);
    }

    protected function getPasswordToken()
    {
        $res = $this->client->asForm()->post(self::ACCESS_TOKEN_URL, [
            'grant_type' => self::PASSWORD_GRANT_TYPE,
            'client_id' => $this->client_id,
            'username' => $this->username,
            'password' => $this->password,
        ])->throw(fn ($r, $e) => self::catchHttpRequestError($r, $e))->json();

        throw_if($res['access_token'] === null, new MyIDCacheNotExists());

        $this->auth_code_token = $res['access_token'];
        cache()->put(self::PASSWORD_TOKEN, $res['access_token'], $res['expires_in'] - 10);
        cache()->put(self::PASSWORD_REF_TOKEN, $res['refresh_token'], $res['expires_in'] - 10);
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
