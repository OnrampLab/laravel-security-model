<?php

namespace OnrampLab\SecurityModel\Redactors;

use Illuminate\Support\Str;
use OnrampLab\SecurityModel\Contracts\Redactor;

/**
 * Redact E.164 format phone number to keep first 3 numbers and last 2 numbers
 */
class E164PhoneNumberRedactor implements Redactor
{
    public const PATTERN = '/(\+?)(\d{10,14})/';

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function redact($value)
    {
        $isMatched = preg_match(self::PATTERN, (string) $value);

        if (! $isMatched) {
            return $value;
        }

        $callback = static function (array $matches) {
            $character = '*';
            $replacement = Str::mask($matches[2], $character, 3, strlen($matches[2]) - 5);

            return "{$matches[1]}{$replacement}";
        };

        return preg_replace_callback(self::PATTERN, $callback, (string) $value);
    }
}
