<?php

namespace OnrampLab\SecurityModel\Tests\Classes;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as BaseUser;
use OnrampLab\SecurityModel\Concerns\Securable;
use OnrampLab\SecurityModel\Contracts\Securable as SecurableContract;

class User extends BaseUser implements SecurableContract
{
    use HasFactory;
    use Securable;

    protected $fillable = [
        'email',
    ];

    protected $encryptable = [
        'email' => ['type' => 'string', 'searchable' => true],
    ];

    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }
}
