<?php

namespace App\DTO;

class ChangePasswordDto
{
    public function __construct(
        public readonly string $currentPassword,
        public readonly string $newPassword,
        public readonly string $new_password_confirmation
    ) {}
    public static function fromRequest(array $data): self
    {
        return new self(
            currentPassword: $data['current_password'],
            newPassword: $data['new_password'],
            new_password_confirmation: $data['new_password_confirmation'],
        );
    }
}
