<?php

namespace App\Services;

use App\Mail\HrmsNotificationMail;
use App\Models\Company;
use App\Models\Employee;
use App\Models\HrmsNotification;
use App\Models\NotificationChannelConfiguration;
use App\Models\NotificationDeliveryLog;
use App\Models\NotificationPreference;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Notifications\HrmsGeneralNotification;
use App\Services\Notification\Providers\MockSmsProvider;
use App\Services\Notification\Providers\MockWhatsAppProvider;
use App\Services\Notification\Providers\SmsNotificationProviderInterface;
use App\Services\Notification\Providers\WhatsAppNotificationProviderInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NotificationService
{
    protected SmsNotificationProviderInterface $smsProvider;
    protected WhatsAppNotificationProviderInterface $whatsAppProvider;

    public function __construct(
        protected CompanyContextService $companyContext,
        ?SmsNotificationProviderInterface $smsProvider = null,
        ?WhatsAppNotificationProviderInterface $whatsAppProvider = null
    ) {
        $this->smsProvider = $smsProvider ?? app(MockSmsProvider::class);
        $this->whatsAppProvider = $whatsAppProvider ?? app(MockWhatsAppProvider::class);
    }

    /**
     * Dispatch multi-channel notifications for a user based on template code.
     */
    public function notify(
        User $user,
        string $templateCode,
        array $params = [],
        ?string $idempotencyKey = null,
        ?Employee $employee = null,
        array $channelOverrides = []
    ): array {
        // 1. Idempotency Check
        if ($idempotencyKey) {
            $existing = HrmsNotification::withoutGlobalScopes()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return [
                    'status' => 'Idempotent Duplicate Ignored',
                    'notification_id' => $existing->id,
                    'channels' => ['in_app' => 'already_dispatched'],
                ];
            }
        }

        // 2. Resolve Context & Entities
        $companyId = $user->company_id ?? $this->companyContext->getActiveCompanyId();
        $company = Company::find($companyId);
        $companyName = $company?->name ?? 'Enterprise HRMS';
        $companyCode = $company?->code ?? 'VE';

        $employee = $employee ?? $user->employee();
        $employeeName = $employee?->full_name ?? $user->name;
        $employeeCode = $employee?->employee_code ?? ($user->employee_code ?? 'EMP');

        // Merge standard placeholders
        $mergedParams = array_merge([
            'employee_name' => $employeeName,
            'employee_code' => $employeeCode,
            'company_name' => $companyName,
            'company_code' => $companyCode,
            'action_url' => url('/dashboard'),
        ], $params);

        // 3. Find templates for this template code in company (or system default)
        $templates = NotificationTemplate::withoutGlobalScopes()
            ->where('code', $templateCode)
            ->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            })
            ->where('is_active', true)
            ->get();

        if ($templates->isEmpty()) {
            return [
                'status' => 'No Active Templates Found',
                'channels' => [],
            ];
        }

        $results = [];
        $category = $templates->first()->category;

        // Group templates by channel
        $templatesByChannel = $templates->keyBy('channel');

        $channels = !empty($channelOverrides) 
            ? $channelOverrides 
            : ['in_app', 'email', 'sms', 'whatsapp'];

        $createdNotification = null;

        foreach ($channels as $channel) {
            if (!$templatesByChannel->has($channel)) {
                continue;
            }

            /** @var NotificationTemplate $template */
            $template = $templatesByChannel->get($channel);

            // Channel Enabled in Company Config?
            $channelConfig = NotificationChannelConfiguration::withoutGlobalScopes()
                ->where('channel', $channel)
                ->where(function ($q) use ($companyId) {
                    $q->where('company_id', $companyId)->orWhereNull('company_id');
                })
                ->latest('id')
                ->first();

            if ($channelConfig && !$channelConfig->is_enabled) {
                $results[$channel] = 'Channel Disabled by Company Policy';
                continue;
            }

            // User Preference Check (unless template is mandatory)
            if (!$template->is_mandatory) {
                $pref = NotificationPreference::withoutGlobalScopes()
                    ->where('user_id', $user->id)
                    ->where('category', $category)
                    ->where('channel', $channel)
                    ->first();

                if ($pref && !$pref->is_enabled) {
                    $results[$channel] = 'Suppressed by User Preference';
                    continue;
                }
            }

            // Render template content
            $rendered = $template->render($mergedParams);

            // Dispatch per channel
            switch ($channel) {
                case 'in_app':
                    $createdNotification = $this->dispatchInApp(
                        user: $user,
                        employee: $employee,
                        companyId: $companyId,
                        template: $template,
                        rendered: $rendered,
                        idempotencyKey: $idempotencyKey,
                        category: $category
                    );
                    $results['in_app'] = 'Delivered';
                    break;

                case 'email':
                    $results['email'] = $this->dispatchEmail(
                        user: $user,
                        employee: $employee,
                        company: $company,
                        template: $template,
                        rendered: $rendered,
                        notificationId: $createdNotification?->id
                    );
                    break;

                case 'sms':
                    $results['sms'] = $this->dispatchSms(
                        user: $user,
                        employee: $employee,
                        companyId: $companyId,
                        template: $template,
                        rendered: $rendered,
                        notificationId: $createdNotification?->id
                    );
                    break;

                case 'whatsapp':
                    $results['whatsapp'] = $this->dispatchWhatsApp(
                        user: $user,
                        employee: $employee,
                        companyId: $companyId,
                        template: $template,
                        params: $mergedParams,
                        notificationId: $createdNotification?->id
                    );
                    break;
            }
        }

        return [
            'status' => 'Dispatched',
            'notification_id' => $createdNotification?->id,
            'channels' => $results,
        ];
    }

    protected function dispatchInApp(
        User $user,
        ?Employee $employee,
        ?int $companyId,
        NotificationTemplate $template,
        array $rendered,
        ?string $idempotencyKey,
        string $category
    ): HrmsNotification {
        $notificationId = Str::uuid()->toString();

        $dataPayload = [
            'title' => $rendered['subject'] ?? $template->name,
            'body' => $rendered['body'],
            'action_url' => $rendered['action_url'] ?? $template->action_url,
            'category' => $category,
            'icon' => $this->getCategoryIcon($category),
        ];

        $notification = HrmsNotification::create([
            'id' => $notificationId,
            'type' => HrmsGeneralNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'company_id' => $companyId,
            'employee_id' => $employee?->id,
            'category' => $category,
            'event_type' => $template->event_type,
            'channel' => 'in_app',
            'template_id' => $template->id,
            'data' => $dataPayload,
            'delivery_status' => 'delivered',
            'sent_at' => now(),
            'idempotency_key' => $idempotencyKey,
        ]);

        NotificationDeliveryLog::create([
            'company_id' => $companyId,
            'notification_id' => $notificationId,
            'user_id' => $user->id,
            'employee_id' => $employee?->id,
            'channel' => 'in_app',
            'recipient_address' => $user->email ?? "user-{$user->id}",
            'status' => 'delivered',
            'provider_name' => 'database',
            'payload' => $dataPayload,
            'dispatched_at' => now(),
            'delivered_at' => now(),
        ]);

        return $notification;
    }

    protected function dispatchEmail(
        User $user,
        ?Employee $employee,
        ?Company $company,
        NotificationTemplate $template,
        array $rendered,
        ?string $notificationId
    ): string {
        $email = $employee?->email ?: $user->email;
        if (empty($email)) {
            return 'Skipped (No Email Address)';
        }

        $mailable = new HrmsNotificationMail(
            subjectText: $rendered['subject'] ?? $template->name,
            bodyText: $rendered['body'],
            companyName: $company?->name ?? 'Enterprise HRMS',
            employeeName: $employee?->full_name ?? $user->name,
            actionUrl: $rendered['action_url'] ?? $template->action_url,
            category: $template->category,
            companyCode: $company?->code ?? 'VE'
        );

        try {
            Mail::to($email)->queue($mailable);

            NotificationDeliveryLog::create([
                'company_id' => $company?->id,
                'notification_id' => $notificationId,
                'user_id' => $user->id,
                'employee_id' => $employee?->id,
                'channel' => 'email',
                'recipient_address' => $email,
                'status' => 'dispatched',
                'provider_name' => config('mail.default', 'log'),
                'payload' => [
                    'subject' => $rendered['subject'],
                    'body' => $rendered['body'],
                    'action_url' => $rendered['action_url'],
                ],
                'dispatched_at' => now(),
            ]);

            return 'Dispatched';
        } catch (\Throwable $e) {
            Log::error('NotificationService email delivery error: ' . $e->getMessage());

            NotificationDeliveryLog::create([
                'company_id' => $company?->id,
                'notification_id' => $notificationId,
                'user_id' => $user->id,
                'employee_id' => $employee?->id,
                'channel' => 'email',
                'recipient_address' => $email,
                'status' => 'failed',
                'provider_name' => config('mail.default', 'log'),
                'failure_reason' => $e->getMessage(),
                'dispatched_at' => now(),
            ]);

            return 'Failed: ' . $e->getMessage();
        }
    }

    protected function dispatchSms(
        User $user,
        ?Employee $employee,
        ?int $companyId,
        NotificationTemplate $template,
        array $rendered,
        ?string $notificationId
    ): string {
        $phone = $employee?->phone ?: $user->phone;
        if (empty($phone)) {
            return 'Skipped (No Mobile Number)';
        }

        $res = $this->smsProvider->sendSms(
            to: $phone,
            message: $rendered['body'],
            options: ['template_code' => $template->code]
        );

        $status = $res['success'] ? 'delivered' : 'failed';

        NotificationDeliveryLog::create([
            'company_id' => $companyId,
            'notification_id' => $notificationId,
            'user_id' => $user->id,
            'employee_id' => $employee?->id,
            'channel' => 'sms',
            'recipient_address' => substr($phone, 0, 3) . '****' . substr($phone, -4),
            'status' => $status,
            'provider_name' => $res['provider'] ?? 'mock_sms',
            'provider_message_id' => $res['message_id'] ?? null,
            'payload' => ['body' => $rendered['body']],
            'provider_response' => json_encode($res['response'] ?? []),
            'failure_reason' => $res['error'] ?? null,
            'dispatched_at' => now(),
            'delivered_at' => $res['success'] ? now() : null,
        ]);

        return $res['success'] ? 'Delivered' : 'Failed: ' . ($res['error'] ?? 'Unknown');
    }

    protected function dispatchWhatsApp(
        User $user,
        ?Employee $employee,
        ?int $companyId,
        NotificationTemplate $template,
        array $params,
        ?string $notificationId
    ): string {
        $phone = $employee?->phone ?: $user->phone;
        if (empty($phone)) {
            return 'Skipped (No Mobile Number)';
        }

        $res = $this->whatsAppProvider->sendWhatsApp(
            to: $phone,
            templateCode: $template->code,
            parameters: $params
        );

        $status = $res['success'] ? 'delivered' : 'failed';

        NotificationDeliveryLog::create([
            'company_id' => $companyId,
            'notification_id' => $notificationId,
            'user_id' => $user->id,
            'employee_id' => $employee?->id,
            'channel' => 'whatsapp',
            'recipient_address' => substr($phone, 0, 3) . '****' . substr($phone, -4),
            'status' => $status,
            'provider_name' => $res['provider'] ?? 'mock_whatsapp',
            'provider_message_id' => $res['message_id'] ?? null,
            'payload' => ['template_code' => $template->code, 'params' => $params],
            'provider_response' => json_encode($res['response'] ?? []),
            'failure_reason' => $res['error'] ?? null,
            'dispatched_at' => now(),
            'delivered_at' => $res['success'] ? now() : null,
        ]);

        return $res['success'] ? 'Delivered' : 'Failed: ' . ($res['error'] ?? 'Unknown');
    }

    public function retryFailed(NotificationDeliveryLog $deliveryLog): array
    {
        if ($deliveryLog->status !== 'failed') {
            return ['status' => 'Ignored', 'message' => 'Delivery is not in failed status.'];
        }

        $deliveryLog->increment('attempt_number');
        $channel = $deliveryLog->channel;
        $res = ['success' => false, 'error' => 'Unknown channel'];

        if ($channel === 'sms') {
            $payload = $deliveryLog->payload ?? [];
            $res = $this->smsProvider->sendSms(
                to: $deliveryLog->recipient_address,
                message: $payload['body'] ?? 'Retry Alert'
            );
        } elseif ($channel === 'whatsapp') {
            $payload = $deliveryLog->payload ?? [];
            $res = $this->whatsAppProvider->sendWhatsApp(
                to: $deliveryLog->recipient_address,
                templateCode: $payload['template_code'] ?? 'GENERAL_ALERT',
                parameters: $payload['params'] ?? []
            );
        } elseif ($channel === 'email') {
            $res = ['success' => true, 'provider' => 'log', 'message_id' => 'RETRY-EML-' . Str::uuid()];
        }

        if ($res['success']) {
            $deliveryLog->update([
                'status' => 'delivered',
                'failure_reason' => null,
                'delivered_at' => now(),
                'provider_response' => json_encode($res['response'] ?? []),
            ]);
            return ['status' => 'Success', 'message' => 'Notification re-delivered successfully.'];
        }

        $deliveryLog->update([
            'failure_reason' => $res['error'] ?? 'Retry attempt failed',
        ]);

        return ['status' => 'Failed', 'message' => $res['error'] ?? 'Retry attempt failed'];
    }

    public function markAsRead(User $user, string $notificationId): bool
    {
        $notification = HrmsNotification::withoutGlobalScopes()
            ->where('id', $notificationId)
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', User::class)
            ->first();

        if ($notification) {
            $notification->update(['read_at' => now()]);
            return true;
        }

        return false;
    }

    public function markAllAsRead(User $user): int
    {
        return HrmsNotification::withoutGlobalScopes()
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', User::class)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function getUnreadCount(User $user): int
    {
        return HrmsNotification::withoutGlobalScopes()
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', User::class)
            ->whereNull('read_at')
            ->count();
    }

    public function getRecentNotifications(User $user, int $limit = 5): Collection
    {
        return HrmsNotification::withoutGlobalScopes()
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', User::class)
            ->latest()
            ->take($limit)
            ->get();
    }

    public function getUserPreferences(User $user): Collection
    {
        $categories = [
            'Onboarding',
            'Attendance',
            'Shift',
            'Leave',
            'Payroll',
            'Loans',
            'Settlement',
            'Compliance',
            'Security',
        ];

        $channels = ['in_app', 'email', 'sms', 'whatsapp'];
        $companyId = $user->company_id ?? $this->companyContext->getActiveCompanyId();

        // Ensure default preferences exist
        foreach ($categories as $cat) {
            foreach ($channels as $chan) {
                NotificationPreference::firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'user_id' => $user->id,
                        'category' => $cat,
                        'channel' => $chan,
                    ],
                    [
                        'employee_id' => $user->employee()?->id,
                        'is_enabled' => true,
                    ]
                );
            }
        }

        return NotificationPreference::where('user_id', $user->id)->get();
    }

    public function updateUserPreferences(User $user, array $submittedPrefs): bool
    {
        $companyId = $user->company_id ?? $this->companyContext->getActiveCompanyId();
        $employeeId = $user->employee()?->id;

        // Categories that are mandatory and CANNOT be turned off
        $mandatoryCategories = ['Compliance', 'Security'];

        foreach ($submittedPrefs as $cat => $channels) {
            foreach ($channels as $chan => $enabled) {
                $isEnabled = (bool) $enabled;

                if (in_array($cat, $mandatoryCategories)) {
                    $isEnabled = true; // Force enabled for compliance & security
                }

                NotificationPreference::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'category' => $cat,
                        'channel' => $chan,
                    ],
                    [
                        'company_id' => $companyId,
                        'employee_id' => $employeeId,
                        'is_enabled' => $isEnabled,
                    ]
                );
            }
        }

        return true;
    }

    protected function getCategoryIcon(string $category): string
    {
        return match ($category) {
            'Onboarding' => 'bi-person-check',
            'Attendance' => 'bi-calendar-check',
            'Shift' => 'bi-clock-history',
            'Leave' => 'bi-calendar-range',
            'Payroll' => 'bi-cash-coin',
            'Loans' => 'bi-bank',
            'Settlement' => 'bi-box-arrow-right',
            'Compliance' => 'bi-shield-check',
            'Security' => 'bi-lock-fill',
            default => 'bi-bell',
        };
    }
}

