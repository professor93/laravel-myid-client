# MYID client for Laravel application

[![Latest Version on Packagist](https://img.shields.io/packagist/v/uzbek/laravel-myid-client.svg?style=flat-square)](https://packagist.org/packages/uzbek/laravel-myid-client)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/professor93/laravel-myid-client/run-tests?label=tests)](https://github.com/professor93/laravel-myid-client/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/professor93/laravel-myid-client/Fix%20PHP%20code%20style%20issues?label=code%20style)](https://github.com/professor93/laravel-myid-client/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/uzbek/laravel-myid-client.svg?style=flat-square)](https://packagist.org/packages/uzbek/laravel-myid-client)


## Installation

You can install the package via composer:

```bash
composer require uzbek/laravel-myid-client
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="myid-client-config"
```

This is the contents of the published config file:

```php
return [
    'base_url' => env('MYID_BASE_URL', 'base_url'),
    'client_id' => env('MYID_CLIENT_ID', 'client_id'),
    'client_secret' => env('MYID_CLIENT_SECRET', 'client_secret'),
    'username' => env('MYID_USERNAME', 'username'),
    'password' => env('MYID_PASSWORD', 'password'),
];
```

## Usage

```php
$laravelMyidClient = new Uzbek\LaravelMyidClient();
echo $laravelMyidClient->echoPhrase('Hello, Uzbek!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Mr.Professor](https://github.com/professor93)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
