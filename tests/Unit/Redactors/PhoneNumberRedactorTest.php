<?php

namespace OnrampLab\SecurityModel\Tests\Unit\Redactors;

use OnrampLab\SecurityModel\Redactors\PhoneNumberRedactor;
use OnrampLab\SecurityModel\Tests\TestCase;

class PhoneNumberRedactorTest extends TestCase
{
    private PhoneNumberRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redactor = $this->app->make(PhoneNumberRedactor::class);
    }

    /**
     * @test
     * @testWith ["(123) 456-7890", "(123) ***-**90"]
     *           ["123-456-7890", "123-***-**90"]
     *           ["123 456 7890", "123 *** **90"]
     *           ["1234567890", "123*****90"]
     */
    public function redact_should_work(string $originalValue, string $expectedValue): void
    {
        $actualValue = $this->redactor->redact($originalValue);

        $this->assertEquals($expectedValue, $actualValue);
    }
}
