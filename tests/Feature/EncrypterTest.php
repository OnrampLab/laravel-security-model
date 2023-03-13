<?php

namespace OnrampLab\SecurityModel\Tests\Feature;

use OnrampLab\SecurityModel\Encrypter;
use OnrampLab\SecurityModel\Tests\TestCase;
use OnrampLab\SecurityModel\ValueObjects\EncryptableField;
use ParagonIE\ConstantTime\Hex;

class EncrypterTest extends TestCase
{
    private string $key;

    private EncryptableField $field;

    private Encrypter $encrypter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->key = Hex::encode(random_bytes(32));
        $this->field = new EncryptableField(['name' => 'email', 'type' => 'string', 'is_searchable' => true]);
        $this->encrypter = new Encrypter('users', [$this->field]);
    }

    /**
     * @test
     * @testWith [{"email": "test@gmail.com"}, true]
     *           [{"email": null}, false]
     */
    public function encrypt_row_should_work(array $originalRow, bool $expectedResult): void
    {
        $encryptedRow = $this->encrypter->encryptRow($this->key, $originalRow);

        $this->assertEquals($expectedResult, $originalRow['email'] !== $encryptedRow['email']);
    }

    /**
     * @test
     * @testWith [{"email": "test@gmail.com"}]
     *           [{"email": null}]
     */
    public function decrypt_row_should_work(array $originalRow): void
    {
        $encryptedRow = $this->encrypter->encryptRow($this->key, $originalRow);
        $decryptedRow = $this->encrypter->decryptRow($this->key, $encryptedRow);

        $this->assertEquals($originalRow['email'], $decryptedRow['email']);
    }

    /**
     * @test
     * @testWith [{"email": "test@gmail.com"}, true]
     *           [{"email": null}, false]
     */
    public function generate_blind_indices_should_work(array $originalRow, bool $expectedResult): void
    {
        $blindIndices = $this->encrypter->generateBlindIndices($this->key, $originalRow);
        $expectedIndexName = $this->encrypter->formatBlindIndexName('email');

        $this->assertArrayHasKey($expectedIndexName, $blindIndices);
        $this->assertEquals($expectedResult, $originalRow['email'] !== $blindIndices[$expectedIndexName]);
    }
}
