<?php

namespace OnrampLab\SecurityModel\Redactors;

use Illuminate\Support\Str;
use OnrampLab\SecurityModel\Contracts\Redactor;

/**
 * Redact email name to keep first initial and would keep last letter if it’s longer or equal to 5
 */
class EmailRedactor implements Redactor
{
    public const PATTERN = '/([\w.+-]+)(@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/';

    /**
     * @param mixed $value
     */
    public function redact($value): string
    {
        $isMatched = preg_match(self::PATTERN, (string) $value);

        if (! $isMatched) {
            return $value;
        }

        $callback = function (array $matches) {
            $character = '*';
            $length = Str::length($matches[1]) >= 5 ?  Str::length($matches[1]) - 2 : null;
            $replacement = Str::mask($matches[1], $character, 1, $length);

            return "{$replacement}{$matches[2]}";
        };

        return preg_replace_callback(self::PATTERN, $callback, (string) $value);
    }
}
