<?php

namespace OnrampLab\SecurityModel\Tests\Unit\Redactors;

use OnrampLab\SecurityModel\Redactors\SecretRedactor;
use OnrampLab\SecurityModel\Tests\Classes\User;
use OnrampLab\SecurityModel\Tests\TestCase;

class SecretRedactorTest extends TestCase
{
    private User $user;

    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->make();
        $this->redactor = $this->app->make(SecretRedactor::class);
    }

    /**
     * @test
     * @testWith ["John Smith", "**********"]
     *           ["(123) 456-7890", "**************"]
     *           ["97003", "*****"]
     */
    public function redact_should_work(string $originalValue, string $expectedValue): void
    {
        $actualValue = $this->redactor->redact($originalValue, $this->user);

        $this->assertEquals($expectedValue, $actualValue);
    }
}
