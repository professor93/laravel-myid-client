<?php

namespace Uzbek\LaravelMyidClient\Services;

use Uzbek\LaravelMyidClient\Exceptions\MyIDNotAuthorizedException;

class MyIDSdk extends Service
{
    const GET_BY_EXTERNAL_ID = 'authentication/authentication-request-status-by-external';

    const ME_URL = 'users/me';

    public function __construct(
        protected ?string $auth_code_token = null,
        protected ?string $password_token = null,
    ) {
        parent::__construct();
    }

    public function me()
    {
        throw_if($this->auth_code_token === null, new MyIDNotAuthorizedException);

        return $this->client->withToken($this->auth_code_token)
            ->get(self::ME_URL)
            ->throw(fn ($r, $e) => self::catchHttpRequestError($r, $e))
            ->json('profile');
    }

    /**
     * @throws \Uzbek\LaravelMyidClient\Exceptions\BadRequest
     * @throws \Throwable
     * @throws \Uzbek\LaravelMyidClient\Exceptions\Forbidden
     */
    public function getByExternalId(string $external_id)
    {
        throw_if($this->password_token === null && $this->client_id === null, new MyIDNotAuthorizedException);

        return $this->client->asForm()->withToken($this->password_token)->post(self::GET_BY_EXTERNAL_ID, [
            'client_id' => $this->client_id,
            'external_id' => $external_id,
        ])->throw(fn ($r, $e) => self::catchHttpRequestError($r, $e))->json();
    }
}
