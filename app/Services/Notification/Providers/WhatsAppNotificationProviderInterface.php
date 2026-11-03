<?php

namespace App\Services\Notification\Providers;

interface WhatsAppNotificationProviderInterface
{
    /**
     * Send a WhatsApp business message using an approved HSM template.
     *
     * @param string $to Recipient phone number (E.164 format)
     * @param string $templateCode Approved business template code
     * @param array $parameters Dynamic body parameters
     * @param array $options Additional options (media URL, buttons, language)
     * @return array [
     *     'success' => bool,
     *     'message_id' => string|null,
     *     'provider' => string,
     *     'response' => mixed,
     *     'error' => string|null
     * ]
     */
    public function sendWhatsApp(string $to, string $templateCode, array $parameters = [], array $options = []): array;
}

