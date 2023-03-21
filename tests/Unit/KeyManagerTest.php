<?php

namespace OnrampLab\SecurityModel\Tests\Unit;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use OnrampLab\SecurityModel\Contracts\KeyProvider;
use OnrampLab\SecurityModel\KeyManager;
use OnrampLab\SecurityModel\Models\EncryptionKey;
use OnrampLab\SecurityModel\Tests\TestCase;
use OnrampLab\SecurityModel\ValueObjects\Ciphertext;
use ParagonIE\ConstantTime\Hex;

class KeyManagerTest extends TestCase
{
    private array $config;

    private string $providerName;

    private MockInterface $providerMock;

    private KeyManager $manager;

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $this->config = [
            'driver'   => 'test_driver',
            'key_id' => Str::uuid()->toString(),
        ];
        $this->providerName = 'test_provider';

        $app['config']->set('security_model.default', $this->providerName);
        $app['config']->set("security_model.providers.{$this->providerName}", $this->config);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->providerMock = Mockery::mock(KeyProvider::class);
        $this->manager = new KeyManager($this->app);
        $this->manager->addProvider($this->config['driver'], function (array $config) {
            return $this->providerMock;
        });
    }

    /**
     * @test
     */
    public function retrieve_encryption_key_should_work(): void
    {
        $expectedKey = EncryptionKey::factory([
            'type' => Str::kebab(Str::camel($this->providerName)),
            'is_primary' => true,
        ])->create();
        $actualKey = $this->manager->retrieveEncryptionKey($this->providerName);

        $this->assertEquals($expectedKey->id, $actualKey->id);
    }

    /**
     * @test
     */
    public function generate_encryption_key_should_work(): void
    {
        $ciphertext = new Ciphertext([
            'key_id' => Str::uuid()->toString(),
            'content' => base64_encode(random_bytes(8)),
        ]);

        $this->providerMock
            ->shouldReceive('encrypt')
            ->once()
            ->withArgs(function (string $plaintext) {
                return strlen(Hex::decode($plaintext)) === 32;
            })
            ->andReturn($ciphertext);

        $encryptionKey = $this->manager->generateEncryptionKey($this->providerName);

        $this->assertEquals($encryptionKey->type, Str::kebab(Str::camel($this->providerName)));
        $this->assertEquals($encryptionKey->master_key_id, $ciphertext->keyId);
        $this->assertEquals($encryptionKey->data_key, $ciphertext->content);
        $this->assertEquals($encryptionKey->is_primary, true);
    }

    /**
     * @test
     */
    public function decrypt_encryption_key_should_work(): void
    {
        $encryptionKey = EncryptionKey::factory([
            'type' => Str::kebab(Str::camel($this->providerName)),
        ])->create();
        $expectedText = Hex::encode(random_bytes(32));

        $this->providerMock
            ->shouldReceive('decrypt')
            ->once()
            ->withArgs(function (Ciphertext $ciphertext) use ($encryptionKey) {
                return $ciphertext->keyId === $encryptionKey->master_key_id
                    && $ciphertext->content === $encryptionKey->data_key;
            })
            ->andReturn($expectedText);

        $actualText = $this->manager->decryptEncryptionKey($encryptionKey);

        $this->assertEquals($expectedText, $actualText);
    }

    /**
     * @test
     */
    public function retrieve_hash_key_should_work(): void
    {
        $expectedKey = Hex::encode(random_bytes(32));

        Config::set('security_model.hash_key', $expectedKey);

        $actualKey = $this->manager->retrieveHashKey();

        $this->assertEquals($expectedKey, $actualKey);
    }

    /**
     * @test
     */
    public function generate_hash_key_should_work(): void
    {
        $hashKey = $this->manager->generateHashKey();

        $this->assertEquals(32, strlen(Hex::decode($hashKey)));
    }

    /**
     * @test
     * @testWith ["test_provider"]
     *           [null]
     */
    public function get_name_should_work(?string $providerName): void
    {
        $this->assertEquals($this->providerName, $this->manager->getName($providerName));
    }
}
