<?php

namespace OnrampLab\SecurityModel;

use Illuminate\Support\ServiceProvider;
use OnrampLab\SecurityModel\Console\Commands\GenerateKey;
use OnrampLab\SecurityModel\Console\Commands\RotateKey;
use OnrampLab\SecurityModel\Contracts\KeyManager as KeyManagerContract;
use OnrampLab\SecurityModel\KeyManager;
use OnrampLab\SecurityModel\KeyProviders\AwsKmsKeyProvider;

class SecurityModelServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/security_model.php', 'security_model');

        $this->registerKeyManager();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__ . '/../config/security_model.php' => config_path('security_model.php'),
        ], 'security-model-config');

        $this->registerCommands();
    }

    protected function registerKeyManager(): void
    {
        $this->app->singleton(KeyManagerContract::class, function ($app) {
            return tap(new KeyManager($app), function (KeyManager $manager): void {
                $this->registerKeyProviders($manager);
            });
        });
    }

    protected function registerKeyProviders(KeyManager $manager): void
    {
        $this->registerAwsKmsKeyProviders($manager);
    }

    protected function registerAwsKmsKeyProviders(KeyManager $manager): void
    {
        $manager->addProvider('aws_kms', function (array $config) {
            return new AwsKmsKeyProvider($config);
        });
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateKey::class,
                RotateKey::class,
            ]);
        }
    }
}
