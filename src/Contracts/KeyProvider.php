<?php

namespace OnrampLab\SecurityModel\Contracts;

use OnrampLab\SecurityModel\ValueObjects\Ciphertext;

interface KeyProvider
{
    /**
     * Get id of managed key
     */
    public function getKeyId(): string;

    /**
     * Encrypt plaintext with managed key
     */
    public function encrypt(string $plaintext): Ciphertext;

    /**
     * Decrypt ciphertext with managed key
     */
    public function decrypt(Ciphertext $ciphertext): string;
}
