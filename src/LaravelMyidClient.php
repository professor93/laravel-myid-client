<?php

namespace Uzbek\LaravelMyidClient;

use Illuminate\Support\Facades\Http;
use Uzbek\LaravelMyidClient\Exceptions\MyIDNotAuthorizedException;

class LaravelMyidClient
{
    const AUTH_CODE_GRANT_TYPE = 'authorization_code';

    const PASSWORD_GRANT_TYPE = 'password';

    const CACHE_PREFIX = 'myid_';

    const AUTH_CODE_TOKEN = self::CACHE_PREFIX.'auth_code_token';

    const PASSWORD_TOKEN = self::CACHE_PREFIX.'password_token';

    const AUTH_CODE_REF_TOKEN = self::CACHE_PREFIX.'auth_code_refresh_token';

    const PASSWORD_REF_TOKEN = self::CACHE_PREFIX.'password_refresh_token';

    private ?string $auth_code_token = null;

    private ?string $password_token = null;

    public function __construct(
        private readonly string $base_url,
        private readonly string $client_id,
        private readonly ?string $client_secret = null,
        private readonly ?string $username = null,
        private readonly ?string $password = null,
    ) {
    }

    /**
     * @throws \Uzbek\LaravelMyidClient\Exceptions\MyIDNotAuthorizedException
     */
    public function sdk(string $auth_code): MyIDSdk
    {
        $this->loginByAuthCode($auth_code);

        if ($this->auth_code_token === null) {
            throw new MyIDNotAuthorizedException;
        }

        return new MyIDSdk();
    }

    private function loginByAuthCode(string $auth_code): void
    {
        if (cache()->has(self::AUTH_CODE_TOKEN.$auth_code)) {
            $this->auth_code_token = cache(self::AUTH_CODE_TOKEN);
        } elseif (cache()->has(self::AUTH_CODE_REF_TOKEN.$auth_code)) {
            $this->refreshToken();
        } else {
            // TODO: NOT FINISHED YET
            $json = Http::asForm()->post('https://faced.track.uz/api/v1/oauth2/access-token', [
                'grant_type' => self::AUTH_CODE_GRANT_TYPE,
                'code' => $auth_code,
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
            ])->json();
            $this->auth_code_token = $json->access_token; // get from cache or refresh_token method
        }
    }

    private function refreshToken(): void
    {
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

    private function loginByPassword(): void
    {
    }

    private function debug()
    {
        return [
            $this->base_url,
            $this->client_id,
            $this->client_secret,
            $this->username,
            $this->password,
        ];
    }
}
