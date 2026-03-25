<?php

namespace App\DTO;

class PreferencesDto
{
    public function __construct(
        public readonly bool $newsletterSubscribed,
        public readonly bool $publicProfile,
        public readonly bool $showOnlineStatus
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            newsletterSubscribed: $data['newsletter_subscribed'] ?? false,
            publicProfile:        $data['public_profile'] ?? false,
            showOnlineStatus:     $data['show_online_status'] ?? false
        );
    }

    public function toArray(): array
    {
        return [
            'newsletter_subscribed' => $this->newsletterSubscribed,
            'public_profile'        => $this->publicProfile,
            'show_online_status'    => $this->showOnlineStatus,
        ];
    }
}
