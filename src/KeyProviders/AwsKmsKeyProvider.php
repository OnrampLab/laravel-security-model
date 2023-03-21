<?php

namespace OnrampLab\SecurityModel\KeyProviders;

use Aws\Credentials\Credentials;
use Aws\Kms\KmsClient;
use OnrampLab\SecurityModel\Contracts\KeyProvider;
use OnrampLab\SecurityModel\ValueObjects\Ciphertext;

class AwsKmsKeyProvider implements KeyProvider
{
    protected const ENCRYPTION_ALGORITHM = 'SYMMETRIC_DEFAULT';

    protected KmsClient $client;

    protected string $keyId;

    public function __construct(array $config)
    {
        $this->client = new KmsClient([
            'version' => '2014-11-01',
            'region' => $config['region'],
            'credentials' => new Credentials($config['access_key'], $config['access_secret']),
        ]);
        $this->keyId = $config['key_id'];
    }

    /**
     * Get id of managed key
     */
    public function getKeyId(): string
    {
        return $this->keyId;
    }

    /**
     * Encrypt plaintext with managed key
     */
    public function encrypt(string $plaintext): Ciphertext
    {
        $result = $this->client->encrypt([
            'KeyId' => $this->getKeyId(),
            'Plaintext' => $plaintext,
            'EncryptionAlgorithm' => self::ENCRYPTION_ALGORITHM,
        ]);

        return new Ciphertext([
            'key_id' => $this->getKeyId(),
            'content' => base64_encode($result['CiphertextBlob']),
        ]);
    }

    /**
     * Decrypt ciphertext with managed key
     */
    public function decrypt(Ciphertext $ciphertext): string
    {
        $result = $this->client->decrypt([
            'KeyId' => $ciphertext->keyId,
            'CiphertextBlob' => base64_decode($ciphertext->content),
            'EncryptionAlgorithm' => self::ENCRYPTION_ALGORITHM,
        ]);

        return $result['Plaintext'];
    }
}
