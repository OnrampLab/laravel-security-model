<?php

namespace OnrampLab\SecurityModel\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\App;
use OnrampLab\SecurityModel\Contracts\KeyManager;
use OnrampLab\SecurityModel\Encrypter;
use OnrampLab\SecurityModel\Models\EncryptionKey;
use OnrampLab\SecurityModel\Observers\ModelObserver;

/**
 * @mixin Model
 */
trait Securable
{
    protected static KeyManager $keyManager;

    public static function bootSecurable(): void
    {
        static::observe(ModelObserver::class);

        static::$keyManager = App::make(KeyManager::class);
    }

    public function encryptionKeys(): MorphToMany
    {
        return $this->morphToMany(EncryptionKey::class, 'encryptable', 'model_has_encryption_keys');
    }

    public function isEncrypted(): bool
    {
        return (bool) $this->encryptionKeys->first();
    }

    public function shouldBeEncryptable(): bool
    {
        return true;
    }

    public function encrypt(): void
    {
        if (! $this->shouldBeEncryptable()) {
            return;
        }

        $encryptionKey = $this->encryptionKeys()->first();

        if (! $encryptionKey) {
            $encryptionKey = static::$keyManager->retrieveKey();

            $this->encryptionKeys()->attach($encryptionKey->id);
        }

        $dataKey = static::$keyManager->decryptKey($encryptionKey);
        $encrypter = App::make(Encrypter::class, ['tableName' => $this->getTable(), 'fields' => $this->getEncryptableFields()]);

        $this->setRawAttributes($encrypter->encryptRow($dataKey, $this->getAttributes()));
        $this->saveQuietly();
    }

    public function decrypt(): void
    {
        $encryptionKey = $this->encryptionKeys()->first();

        if (! $encryptionKey) {
            return;
        }

        $dataKey = static::$keyManager->decryptKey($encryptionKey);
        $encrypter = App::make(Encrypter::class, ['tableName' => $this->getTable(), 'fields' => $this->getEncryptableFields()]);

        $this->setRawAttributes($encrypter->decryptRow($dataKey, $this->getAttributes()), true);
    }

    protected function getEncryptableFields(): array
    {
        $fillableFields = $this->getFillable();
        $encryptableFields = array_intersect($this->encryptable ?? [], $fillableFields);
        $attributeFields = array_keys($this->getAttributes());
        $encryptableFields = array_intersect($attributeFields, $encryptableFields);

        return array_values($encryptableFields);
    }
}
