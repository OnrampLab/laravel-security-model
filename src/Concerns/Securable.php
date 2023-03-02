<?php

namespace OnrampLab\SecurityModel\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use InvalidArgumentException;
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

    public function isSearchableEncryptedField(string $fieldName): bool
    {
        $field = Collection::make($this->getEncryptableFields())
            ->first(function (EncryptableField $field) use ($fieldName) {
                return $field->name === $fieldName && $field->isSearchable;
            });

        return (bool) $field;
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

        $encrypter = $this->getEncrypter();
        $dataKey = static::$keyManager->decryptEncryptionKey($encryptionKey);
        $encryptedRow = $encrypter->encryptRow($dataKey, $this->getAttributes());
        $hashKey = static::$keyManager->retrieveHashKey();
        $blindIndices = $encrypter->generateBlindIndices($hashKey, $this->getAttributes());

        $this->setRawAttributes(array_merge($encryptedRow, $blindIndices));
        $this->saveQuietly();
    }

    public function decrypt(): void
    {
        $encryptionKey = $this->encryptionKeys()->first();

        if (! $encryptionKey) {
            return;
        }

        $encrypter = $this->getEncrypter();
        $dataKey = static::$keyManager->decryptEncryptionKey($encryptionKey);

        $this->setRawAttributes($encrypter->decryptRow($dataKey, $this->getAttributes()), true);
    }

    /**
     * @param mixed $value
     */
    public function generateBlindIndex(string $fieldName, $value): array
    {
        if (! $this->isSearchableEncryptedField($fieldName)) {
            throw new InvalidArgumentException("The [{$fieldName}] field is not a searchable encrypted field.");
        }

        $encrypter = $this->getEncrypter();
        $hashKey = static::$keyManager->retrieveHashKey();
        $blindIndices = $encrypter->generateBlindIndices($hashKey, [$fieldName => $value]);
        $indexName = $encrypter->formatBlindIndexName($fieldName);

        return [$indexName => $blindIndices[$indexName]];
    }

    protected function getEncrypter(): Encrypter
    {
        return App::make(Encrypter::class, ['tableName' => $this->getTable(), 'fields' => $this->getEncryptableFields()]);
    }

    protected function getEncryptableFields(): array
    {
        return Collection::make($this->encryptable ?? [])
            ->map(function (array $field, string $name) {
                return new EncryptableField([
                    'name' => $name,
                    'type' => $field['type'],
                    'is_searchable' => data_get($field, 'searchable', false),
                ]);
            })
            ->values()
            ->toArray();
    }
}
