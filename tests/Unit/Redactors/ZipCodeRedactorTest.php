<?php

namespace OnrampLab\SecurityModel\Tests\Unit\Redactors;

use OnrampLab\SecurityModel\Redactors\ZipCodeRedactor;
use OnrampLab\SecurityModel\Tests\TestCase;

class ZipCodeRedactorTest extends TestCase
{
    private ZipCodeRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redactor = $this->app->make(ZipCodeRedactor::class);
    }

    /**
     * @test
     * @testWith ["97003", "9***3"]
     */
    public function redact_should_work(string $originalValue, string $expectedValue): void
    {
        $actualValue = $this->redactor->redact($originalValue);

        $this->assertEquals($expectedValue, $actualValue);
    }
}
