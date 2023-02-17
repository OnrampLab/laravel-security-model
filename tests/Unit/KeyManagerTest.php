<?php

namespace OnrampLab\SecurityModel\Tests\Unit;

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
            'driver'   => 'test',
            'key_id' => Str::uuid()->toString(),
        ];

        $app['config']->set('security_model.default', 'test');
        $app['config']->set('security_model.providers.test', $this->config);
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
    public function retrieve_key_should_work(): void
    {
        $driverName = $this->config['driver'];

        $this->providerMock
            ->shouldReceive('getName')
            ->once()
            ->andReturn($driverName);

        $expectedKey = EncryptionKey::factory([
            'type' => Str::kebab(Str::camel($driverName)),
            'is_primary' => true,
        ])->create();
        $actualKey = $this->manager->retrieveKey($driverName);

        $this->assertEquals($expectedKey->id, $actualKey->id);
    }

    /**
     * @test
     */
    public function generate_key_should_work(): void
    {
        $driverName = $this->config['driver'];
        $ciphertext = new Ciphertext([
            'key_id' => Str::uuid()->toString(),
            'content' => base64_encode(random_bytes(8)),
        ]);

        $this->providerMock
            ->shouldReceive('getName')
            ->once()
            ->andReturn($driverName);

        $this->providerMock
            ->shouldReceive('encrypt')
            ->once()
            ->withArgs(function (string $plaintext) {
                return strlen(Hex::decode($plaintext)) === 32;
            })
            ->andReturn($ciphertext);

        $encryptionKey = $this->manager->generateKey($driverName);

        $this->assertEquals($encryptionKey->type, Str::kebab(Str::camel($driverName)));
        $this->assertEquals($encryptionKey->key_id, $ciphertext->keyId);
        $this->assertEquals($encryptionKey->data_key, $ciphertext->content);
        $this->assertEquals($encryptionKey->is_primary, true);
    }
}
