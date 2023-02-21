<?php

namespace OnrampLab\SecurityModel\Contracts;

use OnrampLab\SecurityModel\ValueObjects\Ciphertext;

interface KeyProvider
{
    public function getKeyId(): string;

    public function encrypt(string $plaintext): Ciphertext;

    public function decrypt(Ciphertext $ciphertext): string;
}
