<?php

namespace OnrampLab\SecurityModel\ValueObjects;

use JsonSerializable;

class EncryptableField implements JsonSerializable
{
    public string $name;

    public string $type;

    public bool $isSearchable;

    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->type = $data['type'];
        $this->isSearchable = data_get($data, 'is_searchable', false);
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'is_searchable' => $this->isSearchable,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
