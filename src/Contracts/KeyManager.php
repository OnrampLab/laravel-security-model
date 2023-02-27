<?php

namespace OnrampLab\SecurityModel\Contracts;

use OnrampLab\SecurityModel\Models\EncryptionKey;

interface KeyManager
{
    /**
     * Retrieve a available encryption key
     */
    public function retrieveKey(?string $providerName = null): EncryptionKey;

    /**
     * Generate a new encryption key
     */
    public function generateKey(?string $providerName = null): EncryptionKey;

    /**
     * Decrypt a encryption key
     */
    public function decryptKey(EncryptionKey $key): string;
}
