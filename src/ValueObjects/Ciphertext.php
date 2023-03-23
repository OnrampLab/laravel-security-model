<?php

namespace OnrampLab\SecurityModel\ValueObjects;

use JsonSerializable;

class Ciphertext implements JsonSerializable
{
    /**
     * The id of key managed by key provider.
     */
    public string $keyId;

    /**
     * The encrypted content.
     */
    public string $content;

    public function __construct(array $data)
    {
        $this->keyId = $data['key_id'];
        $this->content = $data['content'];
    }

    public function toArray(): array
    {
        return [
            'key_id' => $this->keyId,
            'content' => $this->content,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
