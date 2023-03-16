<?php

namespace OnrampLab\SecurityModel\Contracts;

interface Redactor
{
    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function redact($value);
}
