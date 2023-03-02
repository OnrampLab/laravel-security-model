<?php

namespace OnrampLab\SecurityModel\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use OnrampLab\SecurityModel\Contracts\KeyManager;
use OnrampLab\SecurityModel\Encrypter;
use OnrampLab\SecurityModel\Models\EncryptionKey;
use OnrampLab\SecurityModel\Observers\ModelObserver;
use OnrampLab\SecurityModel\ValueObjects\EncryptableField;

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
            $encryptionKey = static::$keyManager->retrieveEncryptionKey();

            $this->encryptionKeys()->attach($encryptionKey->id);
        }

        $dataKey = static::$keyManager->decryptEncryptionKey($encryptionKey);
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

        $dataKey = static::$keyManager->decryptEncryptionKey($encryptionKey);
        $encrypter = App::make(Encrypter::class, ['tableName' => $this->getTable(), 'fields' => $this->getEncryptableFields()]);

        $this->setRawAttributes($encrypter->decryptRow($dataKey, $this->getAttributes()), true);
    }

    protected function getEncryptableFields(): array
    {
        $fields = array_intersect_key($this->encryptable ?? [], array_flip($this->getFillable()));
        $fields = Collection::make($fields)
            ->map(function (array $field, string $name) {
                return new EncryptableField([
                    'name' => $name,
                    'type' => $field['type'],
                    'is_searchable' => data_get($field, 'searchable', false),
                ]);
            })
            ->values()
            ->toArray();

        return $fields;
    }
}
