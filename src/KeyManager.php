<?php

namespace OnrampLab\SecurityModel;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;
use OnrampLab\SecurityModel\Contracts\KeyManager as KeyManagerContract;
use OnrampLab\SecurityModel\Contracts\KeyProvider;

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
