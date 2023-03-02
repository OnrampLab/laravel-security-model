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
     */
    public function encrypt_row_should_work(): void
    {
        $originalRow = [
            'email' => 'test@gmail.com',
        ];
        $encryptedRow = $this->encrypter->encryptRow($this->key, $originalRow);

        $this->assertNotEquals($originalRow['email'], $encryptedRow['email']);
    }

    /**
     * @test
     */
    public function decrypt_row_should_work(): void
    {
        $originalRow = [
            'email' => 'test@gmail.com',
        ];
        $encryptedRow = $this->encrypter->encryptRow($this->key, $originalRow);
        $decryptedRow = $this->encrypter->decryptRow($this->key, $encryptedRow);

        $this->assertEquals($originalRow['email'], $decryptedRow['email']);
    }
}
