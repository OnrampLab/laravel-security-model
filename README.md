# laravel-security-model

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![CircleCI](https://circleci.com/gh/OnrampLab/laravel-security-model.svg?style=shield)](https://circleci.com/gh/OnrampLab/laravel-security-model)
[![Total Downloads](https://img.shields.io/packagist/dt/onramplab/laravel-security-model.svg?style=flat-square)](https://packagist.org/packages/onramplab/laravel-security-model)

A Laravel package providing security for Eloquent model

## Requirements

- PHP >= 7.4;
- composer.

## Features

- Encryption 
  - Easy to use with Laravel Eloquent model
  - Support multiple types of key management service
    - AWS KMS

## Installation

Install the package via composer

```bash
composer require onramplab/laravel-security-model
```

Run migration command to build tables needed in package

```bash
php artisan migrate
```

Also, you can choose to publish the configuration file

```bash
php artisan vendor:publish --tag="security-model-config"
```

## Usage

1. Set up credentials for key provider you want to use for encryption
2. Use the `OnrampLab\SecurityModel\Concerns\Securable` trait in model
4. Implement the `OnrampLab\SecurityModel\Contracts\Securable` in model
3. Set up `$encryptable` array attribute in model to define which fields needed to be encrypted

```php
use Illuminate\Database\Eloquent\Model;
use OnrampLab\SecurityModel\Concerns\Securable;
use OnrampLab\SecurityModel\Contracts\Securable as SecurableContract;

class User extends Model implements SecurableContract
{
    use Securable;

    /**
     * The attributes that are needed to be encrypted.
     */
    protected array $encryptable = [
        'phone',
        'email',
    ];
}

```


## Running Tests

```bash
composer test
```

## Changelog

To keep track, please refer to [CHANGELOG.md](https://github.com/Onramplab/laravel-security-model/blob/master/CHANGELOG.md).

## Contributing

1. Fork it.
2. Create your feature branch (git checkout -b my-new-feature).
3. Make your changes.
4. Run the tests, adding new ones for your own code if necessary (phpunit).
5. Commit your changes (git commit -am 'Added some feature').
6. Push to the branch (git push origin my-new-feature).
7. Create new pull request.

Also please refer to [CONTRIBUTION.md](https://github.com/Onramplab/laravel-security-model/blob/master/CONTRIBUTION.md).

## License

Please refer to [LICENSE](https://github.com/Onramplab/laravel-security-model/blob/master/LICENSE).
