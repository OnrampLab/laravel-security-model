<?php

namespace OnrampLab\SecurityModel\Redactors;

use Illuminate\Support\Str;
use OnrampLab\SecurityModel\Contracts\Redactor;

/**
 * Redact name to keep first initial and would keep last letter if it’s longer or equal to 5
 */
class NameRedactor implements Redactor
{
    public const PATTERN = "/[\p{L}]+(?:[-.' ][\p{L}]+[.]*)*/u";

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
            $length = Str::length($matches[0]) >= 5 ?  Str::length($matches[0]) - 2 : null;

            return Str::mask($matches[0], $character, 1, $length);
        };

        return preg_replace_callback(self::PATTERN, $callback, (string) $value);
    }
}
