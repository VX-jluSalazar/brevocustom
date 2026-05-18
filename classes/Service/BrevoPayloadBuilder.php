<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class BrevoPayloadBuilder
{
    public function baseEvent(string $eventName, string $email, array $eventProperties = [], array $contactProperties = []): array
    {
        return [
            'event_name' => $eventName,
            'event_date' => date(DATE_ATOM),
            'identifiers' => [
                'email_id' => $email,
            ],
            'contact_properties' => $contactProperties,
            'event_properties' => $eventProperties,
        ];
    }
}
