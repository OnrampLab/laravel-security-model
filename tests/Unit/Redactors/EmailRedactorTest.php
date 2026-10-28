<?php

namespace OnrampLab\SecurityModel\Tests\Unit\Redactors;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

use OnrampLab\SecurityModel\Redactors\EmailRedactor;
use OnrampLab\SecurityModel\Tests\Classes\User;
use OnrampLab\SecurityModel\Tests\TestCase;

class EmailRedactorTest extends TestCase
{
    private User $user;

    private EmailRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->make();
        $this->redactor = $this->app->make(EmailRedactor::class);
    }

    /**
     * @test
     * @testWith ["john@example.net", "j***@example.net"]
     *           ["smith@example.net", "s***h@example.net"]
     *           ["hayden@example.com.tw", "h****n@example.com.tw"]
     *           ["rachel-marie.garcia@example.net", "r*****************a@example.net"]
     */
    #[Test]
    #[TestWith(['john@example.net', 'j***@example.net'])]
    #[TestWith(['smith@example.net', 's***h@example.net'])]
    #[TestWith(['hayden@example.com.tw', 'h****n@example.com.tw'])]
    #[TestWith(['rachel-marie.garcia@example.net', 'r*****************a@example.net'])]
    public function redact_should_work(string $originalValue, string $expectedValue): void
    {
        $actualValue = $this->redactor->redact($originalValue, $this->user);

        $this->assertEquals($expectedValue, $actualValue);
    }
}
