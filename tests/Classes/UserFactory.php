<?php

namespace OnrampLab\SecurityModel\Tests\Classes;

use Orchestra\Testbench\Factories\UserFactory as BaseUserFactory;

class UserFactory extends BaseUserFactory
{
    protected $model = User::class;
}
