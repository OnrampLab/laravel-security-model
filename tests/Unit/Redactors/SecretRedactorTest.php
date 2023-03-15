<?php

namespace OnrampLab\SecurityModel\Tests\Unit\Redactors;

use OnrampLab\SecurityModel\Redactors\SecretRedactor;
use OnrampLab\SecurityModel\Tests\TestCase;

class SecretRedactorTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();

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
        $actualValue = $this->redactor->redact($originalValue);

        $this->assertEquals($expectedValue, $actualValue);
    }
}
