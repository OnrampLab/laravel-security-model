<?php

namespace OnrampLab\SecurityModel\Redactors;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use OnrampLab\SecurityModel\Contracts\Redactor;

/**
 * Redact 5-digit zip code to keep first digit and last digit
 */
class ZipCodeRedactor implements Redactor
{
    public const PATTERN = '/\d{5}/';

    /**
     * @param mixed $value
     * @param Model $model
     *
     * @return mixed
     */
    public function redact($value, $model)
    {
        $isMatched = preg_match(self::PATTERN, (string) $value);

        if (! $isMatched) {
            return $value;
        }

        $callback = static function (array $matches) {
            $character = '*';

            return Str::mask($matches[0], $character, 1, 3);
        };

        return preg_replace_callback(self::PATTERN, $callback, (string) $value);
    }
}
