<?php

namespace OnrampLab\SecurityModel\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use OnrampLab\SecurityModel\Contracts\KeyManager;
use OnrampLab\SecurityModel\Exceptions\KeyNotExistedException;
use OnrampLab\SecurityModel\Models\EncryptionKey;

class GenerateKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security-model:generate-key
                            {provider? : The name of key provider}';

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
        DB::beginTransaction();

        try {
            /** @var string|null $providerName */
            $providerName = $this->argument('provider');
            $existedKey = $this->retrieveEncryptionKey($keyManager, $providerName);

            if ($existedKey) {
                $this->info('encryption key already existed');
            } else {
                $keyManager->generateEncryptionKey($providerName);
                $this->info('encryption key creation done');
            }

            DB::commit();

            return Command::SUCCESS;
        } catch (Exception $exception) {
            DB::rollBack();

            $this->error("encryption key creation failed: {$exception->getMessage()}");

            return Command::FAILURE;
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
}
