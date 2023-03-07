<?php

namespace OnrampLab\SecurityModel\Exceptions;

use OnrampLab\SecurityModel\Exceptions\SecurityModelException;

class KeyNotExistedException extends SecurityModelException
{
    public static function create(string $keyName): self
    {
        return new self("Should generate a {$keyName} first.");
    }
}
