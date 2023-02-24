<?php

namespace OnrampLab\SecurityModel\Tests\Unit\Concerns;

use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;
use OnrampLab\SecurityModel\Contracts\KeyManager;
use OnrampLab\SecurityModel\Contracts\Securable;
use OnrampLab\SecurityModel\Models\EncryptionKey;
use OnrampLab\SecurityModel\Tests\Classes\User;
use OnrampLab\SecurityModel\Tests\TestCase;
use ParagonIE\ConstantTime\Hex;

class SecurableTest extends TestCase
{
    private MockInterface $managerMock;

    private EncryptionKey $encryptionKey;

    private Securable $model;

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();
    }

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

        $this->managerMock = $this->mock(KeyManager::class);

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
        $this->managerMock
            ->shouldReceive('retrieveKey')
            ->once()
            ->andReturn($this->encryptionKey);

        $dataKey = Hex::encode(random_bytes(32));

        $this->managerMock
            ->shouldReceive('decryptKey')
            ->once()
            ->with($this->encryptionKey)
            ->andReturn($dataKey);

        $originalAttribute = $this->model->email;

        $this->model->encrypt();

        $encryptedAttribute = $this->model->email;

        $this->assertNotEquals($originalAttribute, $encryptedAttribute);
    }

    /**
     * @test
     */
    public function decrypt_should_work(): void
    {
        $this->model->encryptionKeys()->attach($this->encryptionKey->id);

        $dataKey = Hex::encode(random_bytes(32));

        $this->managerMock
            ->shouldReceive('decryptKey')
            ->withArgs(function (EncryptionKey $key) {
                return $key->id === $this->encryptionKey->id;
            })
            ->andReturn($dataKey);

        $this->model->encrypt();

        $encryptedAttribute = $this->model->email;

        $this->model->decrypt();

        $originalAttribute = $this->model->email;

        $this->assertNotEquals($encryptedAttribute, $originalAttribute);
    }
}
