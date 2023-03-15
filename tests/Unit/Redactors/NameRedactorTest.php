<?php

namespace OnrampLab\SecurityModel\Tests\Unit\Redactors;

use OnrampLab\SecurityModel\Redactors\NameRedactor;
use OnrampLab\SecurityModel\Tests\TestCase;

class NameRedactorTest extends TestCase
{
    private NameRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redactor = $this->app->make(NameRedactor::class);
    }

    /**
     * @test
     * @testWith ["John", "J***"]
     *           ["José", "J***"]
     *           ["Smith", "S***h"]
     *           ["Hayden O'Reilly", "H*************y"]
     *           ["William Jr. Smith", "W***************h"]
     *           ["Rachel-Marie Garcia", "R*****************a"]
     */
    public function redact_should_work(string $originalValue, string $expectedValue): void
    {
        $actualValue = $this->redactor->redact($originalValue);

        $this->assertEquals($expectedValue, $actualValue);
    }
}
