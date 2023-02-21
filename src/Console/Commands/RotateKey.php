<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use OnrampLab\SecurityModel\Contracts\KeyManager;

class RotateKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security-model:rotate-key
                            {provider? : The name of key provider}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotate the primary encryption key.';

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
            $currentKey = $keyManager->retrieveKey($providerName);

            $keyManager->generateKey($providerName);
            $currentKey->deprecate();

            DB::commit();

            $this->info('encryption key rotation done');

            return Command::SUCCESS;
        } catch (Exception $exception) {
            DB::rollBack();

            $this->error("encryption key rotation failed: {$exception->getMessage()}");

            return Command::FAILURE;
        }
    }
}
