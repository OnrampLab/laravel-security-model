<?php

namespace OnrampLab\SecurityModel\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface Securable
{
    public function encryptionKeys(): MorphToMany;

    public function isEncrypted(): bool;

    public function shouldBeEncryptable(): bool;

    public function encrypt(): void;

    public function decrypt(): void;
}
