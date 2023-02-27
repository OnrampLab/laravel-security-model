<?php

namespace OnrampLab\SecurityModel;

use ParagonIE\CipherSweet\Backend\BoringCrypto;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\CipherSweet;
use ParagonIE\CipherSweet\Constants;
use ParagonIE\CipherSweet\EncryptedRow;
use ParagonIE\CipherSweet\KeyProvider\StringProvider;

class Encrypter
{
    /**
     * The name of database table contains encrypted data row
     */
    protected string $tableName;

    /**
     * The fields of row needed to be encrypted
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
        $encryptionRow = $this->buildEncryptionRow($key);

        return $encryptionRow->encryptRow($dataRow);
    }

    /**
     * Decrypt data row with provided key
     */
    public function decryptRow(string $key, array $dataRow): array
    {
        $encryptionRow = $this->buildEncryptionRow($key);

        return $encryptionRow->decryptRow($dataRow);
    }

    /**
     * Format blind index name with provided field name
     */
    public function formatBlindIndexName(string $fieldName): string
    {
        return "{$fieldName}_bidx";
    }

    protected function buildEncryptionRow(string $key): EncryptedRow
    {
        $keyProvider = new StringProvider($key);
        $backend = new BoringCrypto();
        $engine = new CipherSweet($keyProvider, $backend);
        $row = new EncryptedRow($engine, $this->tableName);

        foreach ($this->fields as $field) {
            $row
                ->addField($field, Constants::TYPE_TEXT)
                ->addBlindIndex($field, new BlindIndex($this->formatBlindIndexName($field)));
        }

        return $row;
    }
}
