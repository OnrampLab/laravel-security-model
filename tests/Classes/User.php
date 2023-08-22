<?php

namespace OnrampLab\SecurityModel\Tests\Classes;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as BaseUser;
use OnrampLab\SecurityModel\Concerns\Securable;
use OnrampLab\SecurityModel\Contracts\Securable as SecurableContract;
use OnrampLab\SecurityModel\Redactors\SecretRedactor;

class User extends BaseUser implements SecurableContract
{
    use HasFactory;
    use Securable;

    protected $fillable = [
        'email',
        'email_bidx',
        'is_encryptable',
    ];

    protected $encryptable = [
        'email' => ['type' => 'string', 'searchable' => true],
    ];

    protected $redactable = [
        'email' => SecretRedactor::class,
    ];

    protected $casts = [
        'is_encryptable' => 'boolean',
    ];

    /**
     * Determine if the model should be encryptable.
     */
    public function shouldBeEncryptable(): bool
    {
        return $this->is_encryptable;
    }

    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }
}
