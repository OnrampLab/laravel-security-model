<?php

namespace OnrampLab\SecurityModel\Contracts;

use OnrampLab\SecurityModel\Models\EncryptionKey;

interface KeyManager
{
    /**
     * Retrieve a available encryption key
     */
    public function retrieveEncryptionKey(?string $providerName = null): EncryptionKey;

    /**
     * Generate a new encryption key
     */
    public function generateEncryptionKey(?string $providerName = null): EncryptionKey;

    /**
     * Decrypt a encryption key
     */
    public function decryptEncryptionKey(EncryptionKey $key): string;

    /**
     * Retrieve a available hash key
     */
    public function retrieveHashKey(): string;

    /**
     * Generate a new hash key
     */
    public function generateHashKey(): string;
}
