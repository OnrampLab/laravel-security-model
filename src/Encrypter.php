<?php

namespace OnrampLab\SecurityModel;

use OnrampLab\SecurityModel\ValueObjects\EncryptableField;
use ParagonIE\CipherSweet\Backend\BoringCrypto;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\CipherSweet;
use ParagonIE\CipherSweet\Constants;
use ParagonIE\CipherSweet\EncryptedRow;
use ParagonIE\CipherSweet\KeyProvider\StringProvider;

class Encrypter
{
    public const FIELD_TYPE_MAPPING = [
        'string' => Constants::TYPE_TEXT,
        'json' => Constants::TYPE_JSON,
        'integer' => Constants::TYPE_INT,
        'float' => Constants::TYPE_FLOAT,
        'boolean' => Constants::TYPE_BOOLEAN,
    ];

    /**
     * The name of database table contains encrypted data row
     */
    protected string $tableName;

    /**
     * The fields of row needed to be encrypted
     *
     * @var array<EncryptableField>
     */
    protected array $fields;

    public function __construct(string $tableName, array $fields)
    {
        $this->tableName = $tableName;
        $this->fields = $fields;
    }

    /**
     * Encrypt data row with provided key
     */
    public function encryptRow(string $key, array $dataRow): array
    {
        $encryptionRow = $this->buildEncryptionRow($key, $dataRow);

        return $encryptionRow->encryptRow($dataRow);
    }

    /**
     * Decrypt data row with provided key
     */
    public function decryptRow(string $key, array $dataRow): array
    {
        $encryptionRow = $this->buildEncryptionRow($key, $dataRow);

        return $encryptionRow->decryptRow($dataRow);
    }

    /**
     * Format blind index name with provided field name
     */
    public function formatBlindIndexName(string $fieldName): string
    {
        return "{$fieldName}_bidx";
    }

    protected function buildEncryptionRow(string $key, array $dataRow): EncryptedRow
    {
        $keyProvider = new StringProvider($key);
        $backend = new BoringCrypto();
        $engine = new CipherSweet($keyProvider, $backend);
        $encryptedRow = new EncryptedRow($engine, $this->tableName);
        $dataFields = array_keys($dataRow);

        foreach ($this->fields as $field) {
            if (! in_array($field->name, $dataFields)) {
                continue;
            }

            $encryptedRow->addField($field->name, self::FIELD_TYPE_MAPPING[$field->type]);

            if ($field->isSearchable) {
                $encryptedRow->addBlindIndex($field->name, new BlindIndex($this->formatBlindIndexName($field->name)));
            }
        }

        return $encryptedRow;
    }
}
