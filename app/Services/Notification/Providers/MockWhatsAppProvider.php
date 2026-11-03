<?php

namespace App\Services\Notification\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MockWhatsAppProvider implements WhatsAppNotificationProviderInterface
{
    public function sendWhatsApp(string $to, string $templateCode, array $parameters = [], array $options = []): array
    {
        $maskedTo = strlen($to) > 6 
            ? substr($to, 0, 3) . '****' . substr($to, -4) 
            : '****';

        $messageId = 'MOCK-WA-' . Str::uuid()->toString();

        Log::info("MockWhatsAppProvider: WhatsApp message simulated to {$maskedTo}", [
            'template' => $templateCode,
            'message_id' => $messageId,
            'parameters' => array_keys($parameters),
        ]);

        return [
            'success' => true,
            'message_id' => $messageId,
            'provider' => 'mock_whatsapp',
            'response' => [
                'status' => 'SENT',
                'gateway' => 'WHATSAPP_BUSINESS_CLOUD_MOCK',
                'template_namespace' => 'enterprise_hrms_alerts',
                'timestamp' => now()->toIso8601String(),
            ],
            'error' => null,
        ];
    }
}

