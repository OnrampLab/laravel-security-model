<?php

namespace OnrampLab\SecurityModel\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use OnrampLab\SecurityModel\ValueObjects\EncryptableField;

interface Securable
{
    /**
     * Get encryption keys the model used
     */
    public function encryptionKeys(): MorphToMany;

    /**
     * Get encryptable fields the model defined
     *
     * @return array<EncryptableField>
     */
    public function getEncryptableFields(): array;

    /**
     * Get redactable fields the model defined
     *
     * @return array<string>
     */
    public function getRedactableFields(): array;

    /**
     * Determine if the model is encrypted
     */
    public function isEncrypted(): bool;

    /**
     * Determine if the given field is encryptable
     */
    public function isEncryptableField(string $fieldName, ?bool $isSearchable = null): bool;

    /**
     * Determine if the given field is redactable
     */
    public function isRedactableField(string $fieldName): bool;

    /**
     * Determine if the model should be encryptable.
     */
    public function shouldBeEncryptable(): bool;

    /**
     * Encrypt data of the model
     */
    public function encrypt(): void;

    /**
     * Decrypt data of the model
     */
    public function decrypt(): void;

    /**
     * Generate blind index value for the given field
     *
     * @param mixed $value
     */
    public function generateBlindIndex(string $field, $value): array;
}
