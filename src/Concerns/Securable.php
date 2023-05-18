<?php

namespace OnrampLab\SecurityModel\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use OnrampLab\SecurityModel\Builders\ModelBuilder;
use OnrampLab\SecurityModel\Contracts\KeyManager;
use OnrampLab\SecurityModel\Contracts\Redactor;
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

    /**
     * Get encryption keys the model used
     */
    public function encryptionKeys(): MorphToMany
    {
        return $this->morphToMany(EncryptionKey::class, 'encryptable', 'model_has_encryption_keys');
    }

    /**
     * Get encryptable fields the model defined
     *
     * @return array<EncryptableField>
     */
    public function getEncryptableFields(): array
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

    /**
     * Get redactable fields the model defined
     *
     * @return array<string>
     */
    public function getRedactableFields(): array
    {
        return array_keys($this->redactable ?? []);
    }

    /**
     * Determine if the model is encrypted
     */
    public function isEncrypted(): bool
    {
        return (bool) $this->encryptionKeys->first();
    }

    /**
     * Determine if the given field is encryptable
     */
    public function isEncryptableField(string $fieldName, ?bool $isSearchable = null): bool
    {
        $fields = Collection::make($this->getEncryptableFields())
            ->filter(fn (EncryptableField $field) => $field->name === $fieldName);

        if (! is_null($isSearchable)) {
            $fields = $fields->filter(fn (EncryptableField $field) => $field->isSearchable === $isSearchable);
        }

        return (bool) $fields->first();
    }

    /**
     * Determine if the given field is redactable
     */
    public function isRedactableField(string $fieldName): bool
    {
        return in_array($fieldName, $this->getRedactableFields());
    }

    /**
     * Determine if the model should be encryptable.
     */
    public function shouldBeEncryptable(): bool
    {
        return true;
    }

    /**
     * Encrypt data of the model
     */
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

    /**
     * Decrypt data of the model
     */
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
     * Generate blind index value for the given field
     *
     * @param mixed $value
     */
    public function generateBlindIndex(string $fieldName, $value): array
    {
        if (! $this->isEncryptableField($fieldName, true)) {
            throw new InvalidArgumentException("The [{$fieldName}] field is not a searchable encrypted field.");
        }

        $encrypter = $this->getEncrypter();
        $hashKey = static::$keyManager->retrieveHashKey();
        $blindIndices = $encrypter->generateBlindIndices($hashKey, [$fieldName => $value]);
        $indexName = $encrypter->formatBlindIndexName($fieldName);

        return [$indexName => $blindIndices[$indexName]];
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @see \Illuminate\Database\Eloquent\Model
     */
    public function newEloquentBuilder($query)
    {
        return new ModelBuilder($query);
    }

    /**
     * Get an attribute from the model.
     *
     * @see \Illuminate\Database\Eloquent\Concerns\HasAttributes
     */
    public function getAttribute($key)
    {
        return $this->isRedactedAttribute($key)
            ? $this->getRedactedAttribute($key)
            : parent::getAttribute($key);
    }

    protected function isRedactedAttribute(string $key): bool
    {
        return preg_match('/(.+)_redacted/', $key, $matches) && $this->isRedactableField($matches[1]);
    }

    protected function getRedactedAttribute(string $key): ?string
    {
        $value = $this->getAttributeFromArray($key);

        if ($value) {
            return $value;
        }

        preg_match('/(.+)_redacted/', $key, $matches);

        $originalKey = $matches[1];
        $redactor = $this->resolveRedactorClass($originalKey);
        $value = $redactor->redact($this->getAttributeFromArray($originalKey), $this);
        $shouldCache = Schema::hasColumn($this->getTable(), $key);

        if ($shouldCache) {
            $this->setAttribute($key, $value);
            $this->saveQuietly();
        }

        return $value;
    }

    protected function resolveRedactorClass(string $key): Redactor
    {
        $className = data_get($this->redactable ?? [], $key);

        if (! class_exists($className) || ! is_subclass_of($className, Redactor::class)) {
            throw new InvalidArgumentException("The [{$className}] class is not a valid redactor");
        }

        return App::make($className);
    }

    protected function getEncrypter(): Encrypter
    {
        return App::make(Encrypter::class, ['tableName' => $this->getTable(), 'fields' => $this->getEncryptableFields()]);
    }
}
