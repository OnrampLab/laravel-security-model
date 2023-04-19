<?php

namespace OnrampLab\SecurityModel\Contracts;

use Illuminate\Database\Eloquent\Model;

interface Redactor
{
    /**
     * @param mixed $value
     * @param Model $model
     *
     * @return mixed
     */
    public function redact($value, $model);
}
