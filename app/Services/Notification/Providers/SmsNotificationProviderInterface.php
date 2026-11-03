<?php

namespace App\Services\Notification\Providers;

interface SmsNotificationProviderInterface
{
    /**
     * Send an SMS message.
     *
     * @param string $to Recipient phone number (E.164 or local format)
     * @param string $message Text content of the SMS
     * @param array $options Additional gateway parameters (sender ID, DLT template ID, etc.)
     * @return array [
     *     'success' => bool,
     *     'message_id' => string|null,
     *     'provider' => string,
     *     'response' => mixed,
     *     'error' => string|null
     * ]
     */
    public function sendSms(string $to, string $message, array $options = []): array;
}

