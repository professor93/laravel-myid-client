<?php
/**
 * Created by Sodikmirzo.
 * User: Sodikmirzo Sattorov ( https://github.com/Sodiqmirzo )
 * Date: 10/20/2022
 * Time: 12:28 PM
 */

namespace Uzbek\LaravelMyidClient\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Uzbek\LaravelMyidClient\Exceptions\BadRequest;
use Uzbek\LaravelMyidClient\Exceptions\Forbidden;

abstract class Service
{
    protected mixed $config;

    protected string $client_id;

    protected string $client_secret;

    protected string $username;

    protected string $password;

    protected PendingRequest $client;

    public function __construct()
    {
        $this->config = config('myid-client');
        $this->client_id = $this->config['client_id'];
        $this->client_secret = $this->config['client_secret'];
        $this->username = $this->config['username'];
        $this->password = $this->config['password'];

        $proxy_url = $config['proxy_url'] ?? (($config['proxy_proto'] ?? '').'://'.($config['proxy_host'] ?? '').':'.($config['proxy_port'] ?? '')) ?? '';
        $options = is_string($proxy_url) && str_contains($proxy_url, '://') && strlen($proxy_url) > 12 ? ['proxy' => $proxy_url] : [];

        $this->client = Http::baseUrl($this->config['base_url'])->withOptions($options);
    }

    public static function catchHttpRequestError($res, $e)
    {
        if ($res->status() === 400) {
            throw new BadRequest('Invalid authorization code(Incorrect client_id, client_secret). Please try again.');
        } elseif ($res->status() === 403) {
            throw new Forbidden('Could not validate credentials - access_token is expired or wrong.');
        } else {
            throw $e;
        }
    }
}
