<?php

namespace OnrampLab\SecurityModel;

use Closure;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;
use InvalidArgumentException;
use OnrampLab\SecurityModel\Contracts\KeyManager as KeyManagerContract;
use OnrampLab\SecurityModel\Contracts\KeyProvider;
use OnrampLab\SecurityModel\Exceptions\KeyNotExistedException;
use OnrampLab\SecurityModel\Models\EncryptionKey;
use OnrampLab\SecurityModel\ValueObjects\Ciphertext;
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

    /**
     * The array of decrypted keys.
     */
    protected array $keys = [];

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
    public function retrieveEncryptionKey(?string $providerName = null): EncryptionKey
    {
        $type = Str::kebab(Str::camel($this->getName($providerName)));
        $key = EncryptionKey::where('type', $type)
            ->where('is_primary', true)
            ->first();

        if (! $key) {
            throw KeyNotExistedException::create('encryption key');
        }

        return $key;
    }

    /**
     * Generate a new encryption key
     */
    public function generateEncryptionKey(?string $providerName = null): EncryptionKey
    {
        $type = Str::kebab(Str::camel($this->getName($providerName)));
        $provider = $this->resolveProvider($providerName);
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
     * Decrypt a encryption key
     */
    public function decryptEncryptionKey(EncryptionKey $key): string
    {
        if (isset($this->keys[$key->id])) {
            return $this->keys[$key->id];
        }

        $providerName = Str::snake(Str::camel($key->type));
        $provider = $this->resolveProvider($providerName);
        $ciphertext = new Ciphertext([
            'key_id' => $key->key_id,
            'content' => $key->data_key,
        ]);
        $plaintext = $provider->decrypt($ciphertext);

        $this->keys[$key->id] = $plaintext;

        return $plaintext;
    }

    /**
     * Get the full name for the given key provider.
     */
    public function getName(?string $providerName = null): string
    {
        return $providerName ?: $this->getDefaultProvider();
    }

    /**
     * Resolve a key provider.
     */
    protected function resolveProvider(?string $providerName): KeyProvider
    {
        $config = $this->getProviderConfig($providerName);
        $name = $config['driver'];

        if (! isset($this->providers[$name])) {
            throw new InvalidArgumentException("No key provider for [{$name}].");
        }

        return call_user_func($this->providers[$name], $config);
    }

    /**
     * Get the key provider configuration.
     */
    protected function getProviderConfig(?string $providerName): array
    {
        $name = $providerName ?: $this->getDefaultProvider();
        $config = $this->app['config']["security_model.providers.{$name}"] ?? null;

        if (is_null($config)) {
            throw new InvalidArgumentException("The [{$name}] key provider has not been configured.");
        }

        return $config;
    }

    /**
     * Get the name of default key provider.
     */
    protected function getDefaultProvider(): string
    {
        return $this->app['config']['security_model.default'] ?? '';
    }
}
