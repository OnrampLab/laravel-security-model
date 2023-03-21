<?php

namespace OnrampLab\SecurityModel\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use OnrampLab\SecurityModel\Contracts\KeyManager;
use OnrampLab\SecurityModel\Exceptions\KeyNotExistedException;
use OnrampLab\SecurityModel\KeyProviders\LocalKeyProvider;
use OnrampLab\SecurityModel\Models\EncryptionKey;

class GenerateKey extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security-model:generate-key
                            {provider? : The name of key provider}
                            {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a primary encryption key.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(KeyManager $keyManager): int
    {
        if (! $this->confirmToProceed()) {
            return Command::SUCCESS;
        }

        DB::beginTransaction();

        try {
            /** @var string|null $providerName */
            $providerName = $this->argument('provider');

            $this->generateMasterKey($providerName);
            $this->generateEncryptionKey($keyManager, $providerName);
            $this->generateHashKey($keyManager);

            DB::commit();

            return Command::SUCCESS;
        } catch (Exception $exception) {
            DB::rollBack();

            $this->error("Failed to generate key: {$exception->getMessage()}.");

            return Command::FAILURE;
        }
    }

    private function generateMasterKey(?string $providerName): void
    {
        $providerName = $providerName ?? $this->laravel['config']['security_model.default'] ?? null;
        $providerConfig = $this->laravel['config']["security_model.providers.{$providerName}"] ?? [];

        if (!$providerConfig || $providerConfig['driver'] !== 'local') {
            return;
        }

        if ($providerConfig['key']) {
            $this->info('Master key already existed');
            return;
        }

        $variableName = 'SECURITY_MODEL_MASTER_KEY';
        $key = LocalKeyProvider::generateKey();

        $this->writeKeyIntoEnvironmentFile($variableName, $key);
        $this->info("Master key has been set into [{$variableName}] variable in the .env file successfully.");

        $this->laravel['config']["security_model.providers.{$providerName}.key"] = $key;
    }

    private function generateEncryptionKey(KeyManager $keyManager, ?string $providerName): void
    {
        $existedKey = $this->retrieveEncryptionKey($keyManager, $providerName);

        if ($existedKey) {
            $this->info('Encryption key already existed');
        } else {
            $keyManager->generateEncryptionKey($providerName);
            $this->info('Encryption key has been set into database successfully.');
        }
    }

    private function retrieveEncryptionKey(KeyManager $keyManager, ?string $providerName): ?EncryptionKey
    {
        try {
            return $keyManager->retrieveEncryptionKey($providerName);
        } catch (KeyNotExistedException $exception) {
            return null;
        }
    }

    private function generateHashKey(KeyManager $keyManager): void
    {
        $existedKey = $this->retrieveHashKey($keyManager);

        if ($existedKey) {
            $this->info('Hash key already existed');
        } else {
            $variableName = 'SECURITY_MODEL_HASH_KEY';
            $key = $keyManager->generateHashKey();

            $this->writeKeyIntoEnvironmentFile($variableName, $key);
            $this->info("Hash key has been set into [{$variableName}] variable in the .env file successfully.");
        }
    }

    private function retrieveHashKey(KeyManager $keyManager): ?string
    {
        try {
            return $keyManager->retrieveHashKey();
        } catch (KeyNotExistedException $exception) {
            return null;
        }
    }

    private function writeKeyIntoEnvironmentFile(string $variableName, string $key): void
    {
        $path = App::environmentFilePath();
        /** @var string $input */
        $input = file_get_contents($path);
        $replaced = preg_replace("/^{$variableName}\=/m", "{$variableName}={$key}", $input);

        if ($replaced === $input || $replaced === null) {
            throw new Exception("Unable to set key. No [{$variableName}] variable was found in the .env file.");
        }

        file_put_contents($path, $replaced);
    }
}
