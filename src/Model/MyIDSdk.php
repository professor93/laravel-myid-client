<?php

namespace Uzbek\LaravelMyidClient\Model;


use Uzbek\LaravelMyidClient\LaravelMyidClient;
use Uzbek\LaravelMyidClient\Request;

class MyIDSdk extends Request
{
    public function __construct(
        protected ?string $external_id = null,

    )
    {
        parent::__construct();
    }

    public function me()
    {
        return $this->sendRequest('get', 'users/me', [], [
            'Authorization' => 'Bearer ' . $this->auth_code_token,
            'Accept' => 'application/json'
        ]);
    }

    public function meByExternalId()
    {
        return $this->sendRequest('post', 'authentication/authentication-request-status-by-external', [
            'external_id' => $this->external_id,
            'client_id' =>$this->client_id,
        ], [
            'Authorization' => 'Bearer ' . $this->password_token,
            'Accept' => 'application/json'
        ]);
    }
}
