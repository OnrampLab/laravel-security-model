<?php

namespace OnrampLab\SecurityModel\Redactors;

use Illuminate\Support\Str;
use OnrampLab\SecurityModel\Contracts\Redactor;

/**
 * Redact US format phone number to keep first 3 numbers and last 2 numbers
 */
class PhoneNumberRedactor implements Redactor
{
    public const PATTERN = '/(\(?)(\d{3})(\)?[- ]?)(\d{3})([- ]?)(\d{4})/';

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
            $matches[4] = Str::mask($matches[4], $character, 0);
            $matches[6] = Str::mask($matches[6], $character, 0, 2);
            $tokens = array_slice($matches, 1);

            return implode($tokens);
        };

        return preg_replace_callback(self::PATTERN, $callback, (string) $value);
    }
}
