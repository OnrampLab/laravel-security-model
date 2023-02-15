<?php

namespace OnrampLab\SecurityModel\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;
use OnrampLab\SecurityModel\Models\EncryptionKey;
use ParagonIE\ConstantTime\Hex;

class EncryptionKeyFactory extends Factory
{
    protected $model = EncryptionKey::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'type' => $this->faker->word(),
            'key_id' => $this->faker->uuid(),
            'data_key' => Crypt::encrypt(Hex::encode(random_bytes(32))),
            'is_primary' => $this->faker->boolean(),
        ];
    }
}
