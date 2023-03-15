<?php

namespace OnrampLab\SecurityModel\Redactors;

use Illuminate\Support\Str;
use OnrampLab\SecurityModel\Contracts\Redactor;

/**
 * Redact all value content with mask charater
 */
class SecretRedactor implements Redactor
{
    public const PATTERN = '/\d{5}/';

    /**
     * @param mixed $value
     */
    public function redact($value): string
    {
        $character = '*';

        return Str::mask((string) $value, $character, 0);
    }
}
