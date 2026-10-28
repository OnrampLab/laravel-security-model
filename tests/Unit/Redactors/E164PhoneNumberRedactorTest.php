<?php

namespace OnrampLab\SecurityModel\Tests\Unit\Redactors;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

use OnrampLab\SecurityModel\Redactors\E164PhoneNumberRedactor;
use OnrampLab\SecurityModel\Tests\Classes\User;
use OnrampLab\SecurityModel\Tests\TestCase;

class E164PhoneNumberRedactorTest extends TestCase
{
    private User $user;

    private E164PhoneNumberRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->make();
        $this->redactor = $this->app->make(E164PhoneNumberRedactor::class);
    }

    /**
     * @test
     * @testWith ["+11234567890", "+112******90"]
     *           ["+442223366555", "+442*******55"]
     *           ["+886988777111", "+886*******11"]
     *           ["11234567890", "112******90"]
     */
    #[Test]
    #[TestWith(['+11234567890', '+112******90'])]
    #[TestWith(['+442223366555', '+442*******55'])]
    #[TestWith(['+886988777111', '+886*******11'])]
    #[TestWith(['11234567890', '112******90'])]
    public function redact_should_work(string $originalValue, string $expectedValue): void
    {
        $actualValue = $this->redactor->redact($originalValue, $this->user);

        $this->assertEquals($expectedValue, $actualValue);
    }
}
