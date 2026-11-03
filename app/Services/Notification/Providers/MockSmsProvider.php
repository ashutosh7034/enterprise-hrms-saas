<?php

namespace App\Services\Notification\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MockSmsProvider implements SmsNotificationProviderInterface
{
    public function sendSms(string $to, string $message, array $options = []): array
    {
        // Mask mobile number for privacy: e.g. +91 98****3344
        $maskedTo = strlen($to) > 6 
            ? substr($to, 0, 3) . '****' . substr($to, -4) 
            : '****';

        $messageId = 'MOCK-SMS-' . Str::uuid()->toString();

        Log::info("MockSmsProvider: SMS simulated to {$maskedTo}", [
            'message_id' => $messageId,
            'length' => strlen($message),
            'sender_id' => $options['sender_id'] ?? 'HRMSPL',
        ]);

        return [
            'success' => true,
            'message_id' => $messageId,
            'provider' => 'mock_sms',
            'response' => [
                'status' => 'DELIVERED',
                'gateway' => 'MOCK_GATEWAY_V1',
                'units' => ceil(strlen($message) / 160),
                'timestamp' => now()->toIso8601String(),
            ],
            'error' => null,
        ];
    }
}

