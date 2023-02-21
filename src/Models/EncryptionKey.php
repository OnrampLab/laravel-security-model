<?php

namespace OnrampLab\SecurityModel\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OnrampLab\SecurityModel\Database\Factories\EncryptionKeyFactory;

class EncryptionKey extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'type',
        'key_id',
        'data_key',
        'is_primary',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string>
     */
    protected $casts = [
        'is_primary' => 'boolean',
    ];

    /**
     * Deprecate this key and will not use this one to encrypt data anymore.
     */
    public function deprecate(): void
    {
        $this->fill(['is_primary' => false]);
        $this->save();
    }

    protected static function newFactory(): Factory
    {
        return EncryptionKeyFactory::new();
    }
}
