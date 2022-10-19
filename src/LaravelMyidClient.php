<?php

namespace Uzbek\LaravelMyidClient;

class LaravelMyidClient
{
    public function __construct()
    {
    }

    public function sdk(): MyIDSdk
    {
        return new MyIDSdk();
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
}
