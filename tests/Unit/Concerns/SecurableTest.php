<?php

namespace OnrampLab\SecurityModel\Tests\Unit\Concerns;

use Closure;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

    private string $email;

    private string $hashKey;

    private string $dataKey;

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

        $this->email = 'test@gmail.com';
        $this->hashKey = base64_encode(random_bytes(32));
        $this->dataKey = base64_encode(random_bytes(32));
        $this->encryptionKey = EncryptionKey::factory()->create();
        $this->model = User::factory()->create([
            'email' => Crypt::encrypt($this->email),
            'email_bidx' => Hash::make($this->email),
        ]);
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
     * @testWith ["email", true]
     *           ["phone", false]
     */
    public function is_redactable_fields_should_work(string $fieldName, bool $expectedResult): void
    {
        $actualResult = $this->model->isRedactableField($fieldName);

        $this->assertEquals($expectedResult, $actualResult);
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

        $this->keyManagerMock
            ->shouldReceive('decryptEncryptionKey')
            ->once()
            ->with($this->encryptionKey)
            ->andReturn($this->dataKey);

        $this->keyManagerMock
            ->shouldReceive('retrieveHashKey')
            ->once()
            ->andReturn($this->hashKey);

        $encryptedRow = [
            'email' => Crypt::encrypt($this->email),
        ];

        $this->encrypterMock
            ->shouldReceive('encryptRow')
            ->once()
            ->with($this->dataKey, $this->model->getAttributes())
            ->andReturn($encryptedRow);

        $blindIndices = [
            'email_bidx' => Hash::make($this->email),
        ];

        $this->encrypterMock
            ->shouldReceive('generateBlindIndices')
            ->once()
            ->with($this->hashKey, $this->model->getAttributes())
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

        $this->keyManagerMock
            ->shouldReceive('decryptEncryptionKey')
            ->withArgs(function (EncryptionKey $key) {
                return $key->id === $this->encryptionKey->id;
            })
            ->andReturn($this->dataKey);

        $decryptedRow = [
            'email' => $this->email,
        ];

        $this->encrypterMock
            ->shouldReceive('decryptRow')
            ->once()
            ->with($this->dataKey, $this->model->getAttributes())
            ->andReturn($decryptedRow);

        $this->model->decrypt();

        $this->assertEquals($decryptedRow['email'], $this->model->email);
    }

    /**
     * @test
     */
    public function generate_blind_index_should_work(): void
    {
        $this->keyManagerMock
            ->shouldReceive('retrieveHashKey')
            ->once()
            ->andReturn($this->hashKey);

        $expectedBlindIndices = [
            'email_bidx' => Hash::make('test@gmail.com'),
        ];

        $this->encrypterMock
            ->shouldReceive('generateBlindIndices')
            ->once()
            ->with($this->hashKey, ['email' => 'test@gmail.com'])
            ->andReturn($expectedBlindIndices);

        $this->encrypterMock
            ->shouldReceive('formatBlindIndexName')
            ->once()
            ->with('email')
            ->andReturn('email_bidx');

        $actualBlindIndex = $this->model->generateBlindIndex('email', 'test@gmail.com');

        $this->assertEquals($actualBlindIndex['email_bidx'], $expectedBlindIndices['email_bidx']);
    }

    /**
     * @test
     * @dataProvider encryptedModelDataProvider
     */
    public function search_encrypted_field_via_query_builder_should_work(Closure $prepareData, bool $expectedResult): void
    {
        $this->keyManagerMock
            ->shouldReceive('retrieveHashKey')
            ->andReturn($this->hashKey);

        $blindIndices = [
            'email_bidx' => $this->model->email_bidx,
        ];

        $this->encrypterMock
            ->shouldReceive('generateBlindIndices')
            ->with($this->hashKey, ['email' => $this->email])
            ->andReturn($blindIndices);

        $this->encrypterMock
            ->shouldReceive('formatBlindIndexName')
            ->with('email')
            ->andReturn('email_bidx');

        $expectedModel = Closure::bind($prepareData, $this)();
        $actualModel = $this->model;

        $this->assertEquals($expectedModel && $expectedModel->id === $actualModel->id, $expectedResult);
    }

    /**
     * @test
     */
    public function get_redacted_attribute_should_work(): void
    {
        $this->model->encryptionKeys()->attach($this->encryptionKey->id);

        $this->keyManagerMock
            ->shouldReceive('decryptEncryptionKey')
            ->andReturn($this->dataKey);

        $this->encrypterMock
            ->shouldReceive('decryptRow')
            ->andReturn(['email' => $this->email]);

        $this->model->decrypt();

        $this->assertEquals($this->model->email, $this->email);
        $this->assertEquals($this->model->email_redacted, Str::repeat('*', Str::length($this->email)));
        $this->assertEquals($this->model->name_redacted, null);
    }

    public function encryptedModelDataProvider(): array
    {
        return [
            $this->searchModelWithColumnValueCase(),
            $this->searchModelWithColumnOperatorValueCase(),
            $this->searchModelWithPrefixedColumnCase(),
            $this->searchModelWithOtherColumnCase(),
            $this->searchModelWithClosureCase(),
            $this->searchModelWithOrWhereClauseCase(),
            $this->searchModelWithNullValueCase(),
        ];
    }

    private function searchModelWithColumnValueCase(): array
    {
        $prepareData = function () {
            return User::where('email', $this->email)->first();
        };

        return [$prepareData, true];
    }

    private function searchModelWithColumnOperatorValueCase(): array
    {
        $prepareData = function () {
            return User::where('email', '=', $this->email)->first();
        };

        return [$prepareData, true];
    }

    private function searchModelWithPrefixedColumnCase(): array
    {
        $prepareData = function () {
            return User::where('users.email', $this->email)->first();
        };

        return [$prepareData, true];
    }

    private function searchModelWithOtherColumnCase(): array
    {
        $prepareData = function () {
            return User::where('name', $this->model->name)->first();
        };

        return [$prepareData, true];
    }

    private function searchModelWithClosureCase(): array
    {
        $prepareData = function () {
            return User::where(function ($query) {
                $query->where('email', $this->email);
            })->first();
        };

        return [$prepareData, true];
    }

    private function searchModelWithOrWhereClauseCase(): array
    {
        $prepareData = function () {
            return User::where('name', Str::random())
                ->orWhere('email', $this->email)
                ->first();
        };

        return [$prepareData, true];
    }

    private function searchModelWithNullValueCase(): array
    {
        $prepareData = function () {
            return User::where('email', null)->first();
        };

        return [$prepareData, false];
    }
}
