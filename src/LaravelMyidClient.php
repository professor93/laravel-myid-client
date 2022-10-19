<?php

namespace Uzbek\LaravelMyidClient;

class LaravelMyidClient
{
    public function __construct(
        private $base_url,
        private $client_id,
        private $grant_type,
        private $proxy,
    )
    {
    }
}
