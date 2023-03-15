<?php

namespace OnrampLab\SecurityModel\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use OnrampLab\SecurityModel\ValueObjects\EncryptableField;

interface Securable
{
    public function encryptionKeys(): MorphToMany;

    public function getEncryptableFields(): array;

    public function getRedactableFields(): array;

    public function isEncrypted(): bool;

    public function isEncryptableField(string $fieldName, ?bool $isSearchable = null): bool;

    public function isRedactableField(string $fieldName): bool;

    public function shouldBeEncryptable(): bool;

    public function encrypt(): void;

    public function decrypt(): void;

    /**
     * @param mixed $value
     */
    public function generateBlindIndex(string $field, $value): array;
}
