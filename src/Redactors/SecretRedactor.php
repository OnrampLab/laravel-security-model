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
     *
     * @return mixed
     */
    public function redact($value)
    {
        $character = '*';

        return Str::mask((string) $value, $character, 0);
    }
}
