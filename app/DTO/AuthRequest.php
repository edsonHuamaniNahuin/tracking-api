<?php
// app/DTO/AuthRequest.php

namespace App\DTO;

class AuthRequest
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            email:    $data['email'],
            password: $data['password'],
        );
    }
}
