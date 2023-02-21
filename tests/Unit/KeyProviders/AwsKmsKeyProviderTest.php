<?php

namespace OnrampLab\SecurityModel\Tests\Unit\KeyProviders;

use Aws\Kms\KmsClient;
use Aws\Result;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use OnrampLab\SecurityModel\KeyProviders\AwsKmsKeyProvider as BaseAwsKmsKeyProvider;
use OnrampLab\SecurityModel\Tests\TestCase;
use OnrampLab\SecurityModel\ValueObjects\Ciphertext;

class AwsKmsKeyProvider extends BaseAwsKmsKeyProvider
{
    public function __construct(array $config, KmsClient $client)
    {
        parent::__construct($config);
        $this->client = $client;
    }
}

class AwsKmsKeyProviderTest extends TestCase
{
    private array $config;

    private MockInterface $clientMock;

    private AwsKmsKeyProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = [
            'driver' => 'aws_kms',
            'access_key' => Str::upper(Str::random(20)),
            'access_secret' => Str::random(40),
            'region' => 'us-east-1',
            'key_id' => Str::uuid()->toString(),
        ];

        $this->clientMock = Mockery::mock(KmsClient::class);
        $this->provider = new AWsKmsKeyProvider($this->config, $this->clientMock);
    }

    /**
     * @test
     */
    public function get_key_id_should_work(): void
    {
        $this->assertEquals($this->config['key_id'], $this->provider->getKeyId());
    }

    /**
     * @test
     */
    public function encrypt_should_work(): void
    {
        $plaintext = Str::random();
        $result = new Result(['CiphertextBlob' => random_bytes(8)]);

        $this->clientMock
            ->shouldReceive('encrypt')
            ->once()
            ->with([
                'KeyId' => $this->config['key_id'],
                'Plaintext' => $plaintext,
                'EncryptionAlgorithm' => 'SYMMETRIC_DEFAULT',
            ])
            ->andReturn($result);

        $ciphertext = $this->provider->encrypt($plaintext);

        $this->assertEquals($ciphertext->keyId, $this->config['key_id']);
        $this->assertEquals($ciphertext->content, base64_encode($result['CiphertextBlob']));
    }

    /**
     * @test
     */
    public function decrypt_should_work(): void
    {
        $ciphertext = new Ciphertext([
            'key_id' => Str::uuid()->toString(),
            'content' => base64_encode(random_bytes(8)),
        ]);
        $result = new Result(['Plaintext' => Str::random()]);

        $this->clientMock
            ->shouldReceive('decrypt')
            ->once()
            ->with([
                'KeyId' => $ciphertext->keyId,
                'CiphertextBlob' => base64_decode($ciphertext->content),
                'EncryptionAlgorithm' => 'SYMMETRIC_DEFAULT',
            ])
            ->andReturn($result);

        $plaintext = $this->provider->decrypt($ciphertext);

        $this->assertEquals($plaintext, $result['Plaintext']);
    }
}
