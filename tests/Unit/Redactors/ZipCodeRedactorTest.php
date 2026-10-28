<?php

namespace OnrampLab\SecurityModel\Tests\Unit\Redactors;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

use OnrampLab\SecurityModel\Redactors\ZipCodeRedactor;
use OnrampLab\SecurityModel\Tests\Classes\User;
use OnrampLab\SecurityModel\Tests\TestCase;

class ZipCodeRedactorTest extends TestCase
{
    private User $user;

    private ZipCodeRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->make();
        $this->redactor = $this->app->make(ZipCodeRedactor::class);
    }

    /**
     * @test
     * @testWith ["97003", "9***3"]
     */
    #[Test]
    #[TestWith(['97003', '9***3'])]
    public function redact_should_work(string $originalValue, string $expectedValue): void
    {
        $actualValue = $this->redactor->redact($originalValue, $this->user);

        $this->assertEquals($expectedValue, $actualValue);
    }
}
