<?php

namespace OnrampLab\SecurityModel\Tests\Unit\Redactors;

use OnrampLab\SecurityModel\Redactors\E164PhoneNumberRedactor;
use OnrampLab\SecurityModel\Tests\TestCase;

class E164PhoneNumberRedactorTest extends TestCase
{
    private E164PhoneNumberRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redactor = $this->app->make(E164PhoneNumberRedactor::class);
    }

    /**
     * @test
     * @testWith ["+11234567890", "+112******90"]
     *           ["+442223366555", "+442*******55"]
     *           ["+886988777111", "+886*******11"]
     *           ["11234567890", "112******90"]
     */
    public function redact_should_work(string $originalValue, string $expectedValue): void
    {
        $actualValue = $this->redactor->redact($originalValue);

        $this->assertEquals($expectedValue, $actualValue);
    }
}
