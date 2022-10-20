<?php

namespace Uzbek\LaravelMyidClient;

use Uzbek\LaravelMyidClient\Exceptions\MyIDNotAuthorizedException;
use Uzbek\LaravelMyidClient\Model\MyIDCompareFace;
use Uzbek\LaravelMyidClient\Model\MyIDInPlace;
use Uzbek\LaravelMyidClient\Model\MyIDRedirect;
use Uzbek\LaravelMyidClient\Model\MyIDSdk;
use Uzbek\LaravelMyidClient\Model\MyIDWebsite;

class LaravelMyidClient extends Request
{
    public function sdk(string $auth_code): MyIDSdk
    {
        $this->loginByAuthCode($auth_code);

        if ($this->auth_code_token === null) {
            throw new MyIDNotAuthorizedException;
        }

        return new MyIDSdk();
    }

    public function sdkExternal(string $external_id)
    {
        $this->loginByPassword();

        if ($this->password_token === null) {
            throw new MyIDNotAuthorizedException;
        }

        return new MyIDSdk($external_id);
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
