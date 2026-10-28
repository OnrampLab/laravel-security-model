<?php

namespace OnrampLab\SecurityModel\Tests\Unit\Redactors;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

use OnrampLab\SecurityModel\Redactors\PhoneNumberRedactor;
use OnrampLab\SecurityModel\Tests\Classes\User;
use OnrampLab\SecurityModel\Tests\TestCase;

class PhoneNumberRedactorTest extends TestCase
{
    private User $user;

    private PhoneNumberRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->make();
        $this->redactor = $this->app->make(PhoneNumberRedactor::class);
    }

    /**
     * @test
     * @testWith ["(123) 456-7890", "(123) ***-**90"]
     *           ["123-456-7890", "123-***-**90"]
     *           ["123 456 7890", "123 *** **90"]
     *           ["1234567890", "123*****90"]
     */
    #[Test]
    #[TestWith(['(123) 456-7890', '(123) ***-**90'])]
    #[TestWith(['123-456-7890', '123-***-**90'])]
    #[TestWith(['123 456 7890', '123 *** **90'])]
    #[TestWith(['1234567890', '123*****90'])]
    public function redact_should_work(string $originalValue, string $expectedValue): void
    {
        $actualValue = $this->redactor->redact($originalValue, $this->user);

        $this->assertEquals($expectedValue, $actualValue);
    }
}
