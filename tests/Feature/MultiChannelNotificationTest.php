<?php

namespace Tests\Feature;

use App\Mail\HrmsNotificationMail;
use App\Models\Company;
use App\Models\Employee;
use App\Models\HrmsNotification;
use App\Models\NotificationDeliveryLog;
use App\Models\NotificationPreference;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MultiChannelNotificationTest extends TestCase
{
    protected User $hrUser;
    protected User $employeeUser;
    protected Company $veCompany;
    protected Employee $employee;
    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->veCompany = Company::where('code', 'VE')->first();
        $this->hrUser = User::where('email', 'hr@vishal.com')->first();
        $this->employeeUser = User::where('email', 'employee@vishal.com')->first();
        $this->employee = Employee::withoutGlobalScopes()->where('company_id', $this->veCompany->id)->first();
        $this->notificationService = app(NotificationService::class);
    }

    public function test_can_create_and_dispatch_in_app_notification(): void
    {
        $idempKey = 'test-inapp-' . uniqid();

        $result = $this->notificationService->notify(
            user: $this->employeeUser,
            templateCode: 'ONBOARDING_WELCOME',
            params: ['employee_name' => 'Amit Sharma', 'employee_code' => 'VE-EMP-1001'],
            idempotencyKey: $idempKey,
            employee: $this->employee,
            channelOverrides: ['in_app']
        );

        $this->assertEquals('Dispatched', $result['status']);
        $this->assertNotNull($result['notification_id']);

        $notif = HrmsNotification::find($result['notification_id']);
        $this->assertNotNull($notif);
        $this->assertEquals($this->employeeUser->id, $notif->notifiable_id);
        $this->assertEquals('Onboarding', $notif->category);
        $this->assertEquals('delivered', $notif->delivery_status);
        $this->assertEquals($idempKey, $notif->idempotency_key);
    }

    public function test_in_app_notification_feed_and_badge_count(): void
    {
        // Ensure at least one unread notification exists
        $this->notificationService->notify(
            user: $this->employeeUser,
            templateCode: 'ONBOARDING_WELCOME',
            params: ['employee_name' => 'Amit Sharma'],
            idempotencyKey: 'test-feed-' . uniqid(),
            employee: $this->employee,
            channelOverrides: ['in_app']
        );

        $response = $this->actingAs($this->employeeUser)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->getJson(route('notifications.feed'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'unread_count',
                'notifications' => [
                    '*' => ['id', 'title', 'body', 'action_url', 'category', 'icon', 'is_read', 'time_ago']
                ]
            ]);

        $this->assertGreaterThan(0, $response->json('unread_count'));
    }

    public function test_can_mark_single_notification_as_read(): void
    {
        $res = $this->notificationService->notify(
            user: $this->employeeUser,
            templateCode: 'ONBOARDING_WELCOME',
            params: ['employee_name' => 'Amit Sharma'],
            idempotencyKey: 'test-read-' . uniqid(),
            employee: $this->employee,
            channelOverrides: ['in_app']
        );

        $notifId = $res['notification_id'];
        $notif = HrmsNotification::find($notifId);
        $this->assertNull($notif->read_at);

        $response = $this->actingAs($this->employeeUser)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->post(route('notifications.mark-read', $notifId));

        $response->assertRedirect();
        $notif->refresh();
        $this->assertNotNull($notif->read_at);
    }

    public function test_can_mark_all_notifications_as_read(): void
    {
        // Dispatch multiple
        $this->notificationService->notify(
            user: $this->employeeUser,
            templateCode: 'ONBOARDING_WELCOME',
            params: ['employee_name' => 'Amit Sharma'],
            idempotencyKey: 'test-allread-1-' . uniqid(),
            employee: $this->employee,
            channelOverrides: ['in_app']
        );

        $response = $this->actingAs($this->employeeUser)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->post(route('notifications.mark-all-read'));

        $response->assertRedirect();
        $this->assertEquals(0, $this->notificationService->getUnreadCount($this->employeeUser));
    }

    public function test_email_dispatch_via_queue(): void
    {
        Mail::fake();

        $result = $this->notificationService->notify(
            user: $this->employeeUser,
            templateCode: 'ONBOARDING_WELCOME',
            params: ['employee_name' => 'Sachin Jadhav', 'employee_code' => 'VE-EMP-1001'],
            idempotencyKey: 'test-email-' . uniqid(),
            employee: null,
            channelOverrides: ['email']
        );

        $this->assertEquals('Dispatched', $result['channels']['email']);

        Mail::assertQueued(HrmsNotificationMail::class);

        $this->assertDatabaseHas('notification_delivery_logs', [
            'channel' => 'email',
            'recipient_address' => 'amit.sharma@vishal.internal',
            'status' => 'dispatched',
        ]);
    }

    public function test_sms_mock_dispatch_and_logging(): void
    {
        $idempKey = 'test-sms-' . uniqid();

        $result = $this->notificationService->notify(
            user: $this->employeeUser,
            templateCode: 'EKYC_RESUBMISSION',
            params: ['details' => 'Unclear Aadhaar photo', 'deadline' => '2026-11-20'],
            idempotencyKey: $idempKey,
            employee: $this->employee,
            channelOverrides: ['sms']
        );

        $this->assertEquals('Delivered', $result['channels']['sms']);

        $log = NotificationDeliveryLog::where('channel', 'sms')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertEquals('delivered', $log->status);
        $this->assertEquals('mock_sms', $log->provider_name);
        $this->assertStringContainsString('****', $log->recipient_address); // Masked for privacy
    }

    public function test_whatsapp_mock_dispatch_and_logging(): void
    {
        $idempKey = 'test-wa-' . uniqid();

        $result = $this->notificationService->notify(
            user: $this->employeeUser,
            templateCode: 'LEAVE_APPROVED',
            params: [
                'employee_name' => 'Amit Sharma',
                'leave_type' => 'Casual Leave',
                'start_date' => '2026-11-10',
                'end_date' => '2026-11-12',
            ],
            idempotencyKey: $idempKey,
            employee: $this->employee,
            channelOverrides: ['whatsapp']
        );

        $this->assertEquals('Delivered', $result['channels']['whatsapp']);

        $log = NotificationDeliveryLog::where('channel', 'whatsapp')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertEquals('delivered', $log->status);
        $this->assertEquals('mock_whatsapp', $log->provider_name);
    }

    public function test_notification_idempotency_prevents_duplicate_dispatch(): void
    {
        $key = 'idemp-guard-' . uniqid();

        $first = $this->notificationService->notify(
            user: $this->employeeUser,
            templateCode: 'ONBOARDING_WELCOME',
            params: ['employee_name' => 'Amit Sharma'],
            idempotencyKey: $key,
            employee: $this->employee,
            channelOverrides: ['in_app']
        );

        $this->assertEquals('Dispatched', $first['status']);

        // Second call with same idempotency key
        $second = $this->notificationService->notify(
            user: $this->employeeUser,
            templateCode: 'ONBOARDING_WELCOME',
            params: ['employee_name' => 'Amit Sharma'],
            idempotencyKey: $key,
            employee: $this->employee,
            channelOverrides: ['in_app']
        );

        $this->assertEquals('Idempotent Duplicate Ignored', $second['status']);
        $this->assertEquals($first['notification_id'], $second['notification_id']);

        // Verify only 1 database notification was inserted
        $count = HrmsNotification::where('idempotency_key', $key)->count();
        $this->assertEquals(1, $count);
    }

    public function test_template_placeholder_rendering(): void
    {
        $template = NotificationTemplate::where('code', 'PAYSLIP_AVAILABLE')->where('channel', 'in_app')->first();
        $this->assertNotNull($template);

        $rendered = $template->render([
            'period_month' => 'October 2026',
            'net_pay' => '54,200.00',
            'employee_name' => 'Amit Sharma',
        ]);

        $this->assertStringContainsString('October 2026', $rendered['subject']);
        $this->assertStringContainsString('₹54,200.00', $rendered['body']);
        $this->assertStringNotContainsString('{period_month}', $rendered['body']);
    }

    public function test_user_notification_preferences_suppression(): void
    {
        // Disable 'Payroll' category for 'sms' channel in user preferences
        NotificationPreference::updateOrCreate(
            ['user_id' => $this->employeeUser->id, 'category' => 'Payroll', 'channel' => 'sms'],
            ['company_id' => $this->veCompany->id, 'is_enabled' => false]
        );

        $result = $this->notificationService->notify(
            user: $this->employeeUser,
            templateCode: 'PAYSLIP_AVAILABLE',
            params: ['period_month' => 'October 2026', 'net_pay' => '50,000'],
            idempotencyKey: 'pref-test-' . uniqid(),
            employee: $this->employee,
            channelOverrides: ['sms']
        );

        $this->assertEquals('Suppressed by User Preference', $result['channels']['sms'] ?? null);
    }

    public function test_mandatory_notification_cannot_be_suppressed_by_preference(): void
    {
        // Try to disable 'Onboarding' which has is_mandatory = true for ONBOARDING_WELCOME
        NotificationPreference::updateOrCreate(
            ['user_id' => $this->employeeUser->id, 'category' => 'Onboarding', 'channel' => 'in_app'],
            ['company_id' => $this->veCompany->id, 'is_enabled' => false]
        );

        $result = $this->notificationService->notify(
            user: $this->employeeUser,
            templateCode: 'ONBOARDING_WELCOME',
            params: ['employee_name' => 'Amit Sharma'],
            idempotencyKey: 'mand-test-' . uniqid(),
            employee: $this->employee,
            channelOverrides: ['in_app']
        );

        // Must still be delivered because ONBOARDING_WELCOME is mandatory
        $this->assertEquals('Delivered', $result['channels']['in_app']);
    }

    public function test_failed_notification_retry_workflow(): void
    {
        $log = NotificationDeliveryLog::create([
            'company_id' => $this->veCompany->id,
            'user_id' => $this->employeeUser->id,
            'channel' => 'sms',
            'recipient_address' => '+91 9811223344',
            'status' => 'failed',
            'provider_name' => 'mock_sms',
            'failure_reason' => 'Simulated Carrier Timeout',
            'payload' => ['body' => 'Important HRMS alert'],
            'attempt_number' => 1,
            'dispatched_at' => now(),
        ]);

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->post(route('admin.notifications.logs.retry', $log->id));

        $response->assertRedirect();
        $log->refresh();

        $this->assertEquals('delivered', $log->status);
        $this->assertEquals(2, $log->attempt_number);
        $this->assertNull($log->failure_reason);
    }
}
