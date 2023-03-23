<?php

namespace OnrampLab\SecurityModel\KeyProviders;

use Illuminate\Encryption\Encrypter;
use OnrampLab\SecurityModel\Contracts\KeyProvider;
use OnrampLab\SecurityModel\ValueObjects\Ciphertext;

class LocalKeyProvider implements KeyProvider
{
    protected const ENCRYPTION_ALGORITHM = 'AES-256-CBC';

    protected Encrypter $encrypter;

    protected string $key;

    public function __construct(array $config)
    {
        $this->encrypter = new Encrypter(base64_decode($config['key']), self::ENCRYPTION_ALGORITHM);
        $this->key = $config['key'];
    }

    /**
     * Get id of managed key
     */
    public function getKeyId(): string
    {
        return hash('sha256', $this->key);
    }

    /**
     * Encrypt plaintext with managed key
     */
    public function encrypt(string $plaintext): Ciphertext
    {
        return new Ciphertext([
            'key_id' => $this->getKeyId(),
            'content' => $this->encrypter->encryptString($plaintext),
        ]);
    }

    /**
     * Decrypt ciphertext with managed key
     */
    public function decrypt(Ciphertext $ciphertext): string
    {
        return $this->encrypter->decryptString($ciphertext->content);
    }

    /**
     * Generate managed key
     */
    public static function generateKey(): string
    {
        return base64_encode(Encrypter::generateKey(self::ENCRYPTION_ALGORITHM));
    }
}
