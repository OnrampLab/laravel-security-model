<?php

namespace OnrampLab\SecurityModel;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;
use InvalidArgumentException;
use OnrampLab\SecurityModel\Contracts\KeyManager as KeyManagerContract;
use OnrampLab\SecurityModel\Contracts\KeyProvider;
use OnrampLab\SecurityModel\Models\EncryptionKey;
use ParagonIE\ConstantTime\Hex;

class KeyManager implements KeyManagerContract
{
    /**
     * The application instance.
     */
    protected Application $app;

    /**
     * The array of resolved key providers.
     */
    protected array $providers = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Add a key provider resolver.
     */
    public function addProvider(string $driverName, Closure $resolver): void
    {
        $this->providers[$driverName] = $resolver;
    }

    /**
     * Retrieve a available encryption key
     */
    public function retrieveKey(?string $driverName = null): EncryptionKey
    {
        $provider = $this->resolveProvider($driverName);
        $type = Str::kebab(Str::camel($provider->getName()));
        $key = EncryptionKey::where('type', $type)->where('is_primary', true)->first();

        if (! $key) {
            $key = $this->generateKey($driverName);
        }

        return $key;
    }

    /**
     * Generate a new encryption key
     */
    public function generateKey(?string $driverName = null): EncryptionKey
    {
        $provider = $this->resolveProvider($driverName);
        $type = Str::kebab(Str::camel($provider->getName()));
        $dataKey = Hex::encode(random_bytes(32));
        $ciphertext = $provider->encrypt($dataKey);

        return EncryptionKey::create([
            'type' => $type,
            'key_id' => $ciphertext->keyId,
            'data_key' => $ciphertext->content,
            'is_primary' => true,
        ]);
    }

    /**
     * Resolve a key provider.
     */
    protected function resolveProvider(?string $driverName): KeyProvider
    {
        $config = $this->getProviderConfig($driverName);
        $name = $config['driver'];

        if (! isset($this->providers[$name])) {
            throw new InvalidArgumentException("No key provider for [{$name}].");
        }

        return call_user_func($this->providers[$name], $config);
    }

    /**
     * Get the key provider configuration.
     */
    protected function getProviderConfig(?string $driverName): array
    {
        $name = $driverName ?: $this->getDefaultDriver();
        $config = $this->app['config']["security_model.providers.{$name}"] ?? null;

        if (is_null($config)) {
            throw new InvalidArgumentException("The [{$name}] key provider has not been configured.");
        }

        return $config;
    }

    /**
     * Get the driver name of default key provider.
     */
    protected function getDefaultDriver(): string
    {
        return $this->app['config']['security_model.default'] ?? '';
    }
}
