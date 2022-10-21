<?php
/**
 * Created by Sodikmirzo.
 * User: Sodikmirzo Sattorov ( https://github.com/Sodiqmirzo )
 * Date: 10/20/2022
 * Time: 12:28 PM
 */

namespace Uzbek\LaravelMyidClient;

use Illuminate\Support\Facades\Http;
use Uzbek\LaravelMyidClient\Exceptions\BadRequest;
use Uzbek\LaravelMyidClient\Exceptions\Forbidden;

class Request
{
    private mixed $config;

    const AUTH_CODE_GRANT_TYPE = 'authorization_code';

    const PASSWORD_GRANT_TYPE = 'password';

    const CACHE_PREFIX = 'myid_';

    const AUTH_CODE_TOKEN = self::CACHE_PREFIX . 'auth_code_token';

    const PASSWORD_TOKEN = self::CACHE_PREFIX . 'password_token';

    const AUTH_CODE_REF_TOKEN = self::CACHE_PREFIX . 'auth_code_refresh_token';

    const PASSWORD_REF_TOKEN = self::CACHE_PREFIX . 'password_refresh_token';

    protected ?string $auth_code_token = null;

    protected ?string $password_token = null;

    protected ?string $client_id = null;

    protected ?string $client_secret = null;

    public function __construct()
    {
        $this->config = config('myid-client');
        $this->client_id = $this->config['client_id'];
        $this->client_secret = $this->config['client_secret'];
    }

    public function sendRequest(string $method, string $url, array $data = [], array $headers = [])
    {
        $options = [];
        $proxy_url = $config['proxy_url'] ?? (($config['proxy_proto'] ?? '') . '://' . ($config['proxy_host'] ?? '') . ':' . ($config['proxy_port'] ?? '')) ?? '';
        if (is_string($proxy_url) && str_contains($proxy_url, '://') && strlen($proxy_url) > 12) {
            $options['proxy'] = $proxy_url;
        }
        return Http::baseUrl($this->config['base_url'])->withOptions($options)->asForm()
            ->withHeaders($headers)->$method($url, $data)->throw(function ($response, $e) {
                if ($response->status() === 400) {
                    throw new BadRequest('Invalid authorization code(Incorrect client_id, client_secret). Please try again.');
                }
                if ($response->status() === 403) {
                    throw new Forbidden('Could not validate credentials - access_token is expired or wrong.');
                }
                throw $e;
            })->json();
    }

    protected function getAccessToken(string $auth_code)
    {
        $request = $this->sendRequest('post', 'oauth2/access-token', [
            'grant_type' => self::AUTH_CODE_GRANT_TYPE,
            'code' => $auth_code,
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
        ]);

        if ($request['access_token'] !== null && $request['refresh_token'] !== null) {
            $this->auth_code_token = $request['access_token'];
            $this->putCacheAccessRefreshToken($request['access_token'], $request['refresh_token'], $auth_code, $request['expires_in']);
        }

        return $request;
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

    protected function refreshToken(string $auth_code)
    {
        $request = $this->sendRequest('post', 'oauth2/refresh-token', [
            'refresh_token' => cache()->get(self::AUTH_CODE_REF_TOKEN . $auth_code),
            'client_id' => $this->config['client_id'],
        ], ['Accept' => 'application/json']);

        if ($request['access_token'] !== null && $request['refresh_token'] !== null) {
            $this->auth_code_token = $request['access_token'];
            $this->putCacheAccessRefreshToken($request['access_token'], $request['refresh_token'], $auth_code, $request['expires_in']);
        }

        return $request;
    }

    protected function putCacheAccessRefreshToken(string $access_token, string $refresh_token, string $auth_code, int $expires_in): void
    {
        cache()->put(self::AUTH_CODE_TOKEN . $auth_code, $access_token, $expires_in - 10);
        cache()->put(self::AUTH_CODE_REF_TOKEN . $auth_code, $refresh_token, $expires_in - 10);
    }

    protected function putCachePasswordAccessRefreshToken(string $access_token, string $refresh_token, int $expires_in): void
    {
        cache()->put(self::PASSWORD_TOKEN, $access_token, $expires_in - 10);
        cache()->put(self::PASSWORD_REF_TOKEN, $refresh_token, $expires_in - 10);
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
}
