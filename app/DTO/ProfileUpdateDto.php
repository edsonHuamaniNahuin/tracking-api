<?php

namespace App\DTO;

class ProfileUpdateDto
{
    private array $data;

    private function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function fromRequest(array $validated): self
    {
        $allowed = ['name','username','email','phone','bio','location'];
        $filtered = array_intersect_key($validated, array_flip($allowed));
        return new self($filtered);
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
