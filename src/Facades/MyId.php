<?php

namespace Uzbek\LaravelMyidClient\Facades;

use Illuminate\Support\Facades\Facade;
use Uzbek\LaravelMyidClient\LaravelMyidClient;

/**
 * @see \Uzbek\LaravelMyidClient\LaravelMyidClient
 */
class MyId extends Facade
{
    protected static function getFacadeAccessor()
    {
        return LaravelMyidClient::class;
    }
}
