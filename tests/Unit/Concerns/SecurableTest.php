<?php

namespace OnrampLab\SecurityModel\Tests\Unit\Concerns;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Mockery\MockInterface;
use OnrampLab\SecurityModel\Contracts\KeyManager;
use OnrampLab\SecurityModel\Encrypter;
use OnrampLab\SecurityModel\Models\EncryptionKey;
use OnrampLab\SecurityModel\Tests\Classes\User;
use OnrampLab\SecurityModel\Tests\TestCase;

class SecurableTest extends TestCase
{
    private MockInterface $keyManagerMock;

    private MockInterface $encrypterMock;

    private EncryptionKey $encryptionKey;

    private User $model;

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/../../Migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

        $this->keyManagerMock = $this->mock(KeyManager::class);
        $this->encrypterMock = Mockery::mock(Encrypter::class);

        $this->app->bind(Encrypter::class, fn () => $this->encrypterMock);

        $this->encryptionKey = EncryptionKey::factory()->create();
        $this->model = User::factory()->create();
    }

    /**
     * @test
     */
    public function encryption_keys_relationship_should_work(): void
    {
        $this->model->encryptionKeys()->attach($this->encryptionKey->id);

        $this->assertEquals($this->encryptionKey->id, $this->model->encryptionKeys->first()->id);
    }

    /**
     * @test
     */
    public function is_encrypted_should_work(): void
    {
        $this->model->encryptionKeys()->attach($this->encryptionKey->id);

        $this->assertTrue($this->model->isEncrypted());
    }

    /**
     * @test
     */
    public function should_be_encrytable_should_work(): void
    {
        $this->assertTrue($this->model->shouldBeEncryptable());
    }

    /**
     * @test
     */
    public function encrypt_should_work(): void
    {
        $this->keyManagerMock
            ->shouldReceive('retrieveEncryptionKey')
            ->once()
            ->andReturn($this->encryptionKey);

        $dataKey = base64_encode(random_bytes(32));

        $this->keyManagerMock
            ->shouldReceive('decryptEncryptionKey')
            ->once()
            ->with($this->encryptionKey)
            ->andReturn($dataKey);

        $hashKey = base64_encode(random_bytes(32));

        $this->keyManagerMock
            ->shouldReceive('retrieveHashKey')
            ->once()
            ->andReturn($hashKey);

        $encryptedRow = [
            'email' => Crypt::encrypt('test@gmail.com'),
        ];

        $this->encrypterMock
            ->shouldReceive('encryptRow')
            ->once()
            ->with($dataKey, $this->model->getAttributes())
            ->andReturn($encryptedRow);

        $blindIndices = [
            'email_bidx' => Hash::make('test@gmail.com'),
        ];

        $this->encrypterMock
            ->shouldReceive('generateBlindIndices')
            ->once()
            ->with($hashKey, $this->model->getAttributes())
            ->andReturn($blindIndices);

        $this->model->encrypt();

        $this->assertEquals($encryptedRow['email'], $this->model->email);
        $this->assertEquals($blindIndices['email_bidx'], $this->model->email_bidx);
    }

    /**
     * @test
     */
    public function decrypt_should_work(): void
    {
        $this->model->encryptionKeys()->attach($this->encryptionKey->id);

        $dataKey = base64_encode(random_bytes(32));

        $this->keyManagerMock
            ->shouldReceive('decryptEncryptionKey')
            ->withArgs(function (EncryptionKey $key) {
                return $key->id === $this->encryptionKey->id;
            })
            ->andReturn($dataKey);

        $decryptedRow = [
            'email' => 'test@gmail.com',
        ];

        $this->encrypterMock
            ->shouldReceive('decryptRow')
            ->once()
            ->with($dataKey, $this->model->getAttributes())
            ->andReturn($decryptedRow);

        $this->model->decrypt();

        $this->assertEquals($decryptedRow['email'], $this->model->email);
    }
}
