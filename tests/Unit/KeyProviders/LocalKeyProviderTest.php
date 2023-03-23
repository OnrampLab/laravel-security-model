<?php

namespace OnrampLab\SecurityModel\Tests\Unit\KeyProviders;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use OnrampLab\SecurityModel\KeyProviders\LocalKeyProvider as BaseLocalKeyProvider;
use OnrampLab\SecurityModel\Tests\TestCase;
use OnrampLab\SecurityModel\ValueObjects\Ciphertext;

class LocalKeyProvider extends BaseLocalKeyProvider
{
    public function __construct(array $config, Encrypter $encrypter)
    {
        parent::__construct($config);
        $this->encrypter = $encrypter;
    }
}

class LocalKeyProviderTest extends TestCase
{
    private array $config;

    private MockInterface $encrypterMock;

    private LocalKeyProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = [
            'driver' => 'local',
            'key' => base64_encode(random_bytes(32)),
        ];

        $this->encrypterMock = Mockery::mock(Encrypter::class);
        $this->provider = new LocalKeyProvider($this->config, $this->encrypterMock);
    }

    /**
     * @test
     */
    public function get_key_id_should_work(): void
    {
        $this->assertEquals(hash('sha256', $this->config['key']), $this->provider->getKeyId());
    }

    /**
     * @test
     */
    public function encrypt_should_work(): void
    {
        $plaintext = Str::random();
        $encryptedValue = Str::random();

        $this->encrypterMock
            ->shouldReceive('encryptString')
            ->once()
            ->with($plaintext)
            ->andReturn($encryptedValue);

        $ciphertext = $this->provider->encrypt($plaintext);

        $this->assertEquals($ciphertext->keyId, hash('sha256', $this->config['key']));
        $this->assertEquals($ciphertext->content, $encryptedValue);
    }

    /**
     * @test
     */
    public function decrypt_should_work(): void
    {
        $ciphertext = new Ciphertext([
            'key_id' => hash('sha256', base64_encode(random_bytes(32))),
            'content' => Str::random(),
        ]);
        $decryptedValue = Str::random();

        $this->encrypterMock
            ->shouldReceive('decryptString')
            ->once()
            ->with($ciphertext->content)
            ->andReturn($decryptedValue);

        $plaintext = $this->provider->decrypt($ciphertext);

        $this->assertEquals($plaintext, $decryptedValue);
    }
}
