<?php

namespace OnrampLab\SecurityModel\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\App;
use OnrampLab\SecurityModel\Contracts\KeyManager;
use OnrampLab\SecurityModel\Models\EncryptionKey;
use OnrampLab\SecurityModel\Observers\ModelObserver;
use ParagonIE\CipherSweet\Backend\BoringCrypto;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\CipherSweet;
use ParagonIE\CipherSweet\Constants;
use ParagonIE\CipherSweet\EncryptedRow;
use ParagonIE\CipherSweet\KeyProvider\StringProvider;

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

    public function encrypt(): void
    {
        $encryptionKey = $this->encryptionKeys()->first();

        if (! $encryptionKey) {
            $encryptionKey = static::$keyManager->retrieveKey();

            $this->encryptionKeys()->attach($encryptionKey->id);
        }

        $dataKey = static::$keyManager->decryptKey($encryptionKey);
        $encryptionRow = $this->buildEncryptionRow($dataKey);

        $this->setRawAttributes($encryptionRow->encryptRow($this->getAttributes()));
    }

    public function decrypt(): void
    {
        $encryptionKey = $this->encryptionKeys()->first();

        if (! $encryptionKey) {
            return;
        }

        $dataKey = static::$keyManager->decryptKey($encryptionKey);
        $encryptionRow = $this->buildEncryptionRow($dataKey);

        $this->setRawAttributes($encryptionRow->decryptRow($this->getAttributes()), true);
    }

    protected function buildEncryptionRow(string $dataKey): EncryptedRow
    {
        $keyProvider = new StringProvider($dataKey);
        $backend = new BoringCrypto();
        $engine = new CipherSweet($keyProvider, $backend);
        $row = new EncryptedRow($engine, $this->getTable());
        $fields = $this->getEncryptableFields();

        foreach ($fields as $field) {
            $row
                ->addField($field, Constants::TYPE_TEXT)
                ->addBlindIndex($field, new BlindIndex("{$field}_index"));
        }

        return $row;
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
