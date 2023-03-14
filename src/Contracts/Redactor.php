<?php

namespace OnrampLab\SecurityModel\Contracts;

interface Redactor
{
    /**
     * @param mixed $value
     */
    public function redact($value): string;
}
