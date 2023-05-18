<?php

namespace OnrampLab\SecurityModel\Tests\Feature\Concerns;

use Illuminate\Support\Facades\DB;
use OnrampLab\SecurityModel\Contracts\KeyManager;
use OnrampLab\SecurityModel\Tests\Classes\User;
use OnrampLab\SecurityModel\Tests\TestCase;

class SecurableTest extends TestCase
{
    private string $providerName;

    private KeyManager $keyManager;

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $this->providerName = 'local';

        $app['config']->set('security_model.default', $this->providerName);
        $app['config']->set("security_model.providers.{$this->providerName}.driver", 'local');
        $app['config']->set("security_model.providers.{$this->providerName}.key", base64_encode(random_bytes(32)));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->keyManager = $this->app->make(KeyManager::class);
        $this->keyManager->generateEncryptionKey($this->providerName);

        $this->app['config']->set('security_model.hash_key', $this->keyManager->generateHashKey());
    }

    /**
     * @test
     * @testWith [{"email": "test@gmail.com"}, true]
     *           [{"email": null}, false]
     */
    public function create_model_should_execute_encryption(array $attributes, bool $expectedResult): void
    {
        $eloquentModel = User::factory()->create($attributes);
        $databaseModel = DB::table($eloquentModel->getTable())->find($eloquentModel->id);

        $this->assertEquals($eloquentModel->email, $attributes['email']);
        $this->assertEquals($expectedResult, $databaseModel->email !== $attributes['email']);
        $this->assertEquals($expectedResult, $eloquentModel->email_bidx !== $attributes['email']);

        $eloquentModel->delete();
    }

    /**
     * @test
     * @testWith [{"email": "test@gmail.com"}, true]
     *           [{"email": null}, false]
     */
    public function update_model_should_execute_encryption(array $attributes, bool $expectedResult): void
    {
        $model = User::factory()->create(['email' => 'fake@gmail.com']);
        $model->fill($attributes)->save();

        $eloquentModel = User::find($model->id);
        $databaseModel = DB::table($eloquentModel->getTable())->find($eloquentModel->id);

        $this->assertEquals($eloquentModel->email, $attributes['email']);
        $this->assertEquals($expectedResult, $databaseModel->email !== $attributes['email']);
        $this->assertEquals($expectedResult, $eloquentModel->email_bidx !== $attributes['email']);

        $model->delete();
    }

    /**
     * @test
     * @testWith [{"email": "test@gmail.com"}, "**************"]
     *           [{"email": null}, null]
     */
    public function get_redacted_attribute_should_work(array $attributes, ?string $expectedResult): void
    {
        $model = User::factory()->create($attributes);

        $this->assertEquals($model->email, $attributes['email']);
        $this->assertEquals($model->email_redacted, $expectedResult);
        $this->assertEquals($model->name_redacted, null);

        $model->delete();
    }

    /**
     * @test
     */
    public function get_redacted_attribute_should_save_cache(): void
    {
        $attributes = ['email' => 'test@gmail.com'];
        $eloquentModel = User::factory()->create($attributes);
        $databaseModel = DB::table($eloquentModel->getTable())->find($eloquentModel->id);

        $this->assertNull($databaseModel->email_redacted);

        $expectedValue = '**************';

        $this->assertEquals($expectedValue, $eloquentModel->email_redacted);

        $databaseModel = DB::table($eloquentModel->getTable())->find($eloquentModel->id);

        $this->assertEquals($expectedValue, $databaseModel->email_redacted);

        $eloquentModel->delete();
    }
}
