<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\HrmsNotification;
use App\Models\NotificationChannelConfiguration;
use App\Models\NotificationDeliveryLog;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Notifications\HrmsGeneralNotification;
use App\Services\NotificationService;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationSecurityAndIsolationTest extends TestCase
{
    protected User $hoAdmin;
    protected User $hrUser;
    protected User $managerUser;
    protected User $employeeUser;
    protected Company $veCompany;
    protected Company $vreCompany;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hoAdmin = User::where('email', 'ho_admin@enterprise.com')->first();
        $this->hrUser = User::where('email', 'hr@vishal.com')->first();
        $this->managerUser = User::where('email', 'manager@vishal.com')->first();
        $this->employeeUser = User::where('email', 'employee@vishal.com')->first();

        $this->veCompany = Company::where('code', 'VE')->first();
        $this->vreCompany = Company::where('code', 'VRE')->first();
    }

    public function test_employee_can_only_access_own_notifications(): void
    {
        // Create notification for employeeUser
        $notif1 = HrmsNotification::create([
            'id' => Str::uuid()->toString(),
            'type' => HrmsGeneralNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $this->employeeUser->id,
            'company_id' => $this->veCompany->id,
            'category' => 'Leave',
            'channel' => 'in_app',
            'data' => ['title' => 'Own Leave Approved', 'body' => 'Your leave was approved.'],
            'delivery_status' => 'delivered',
        ]);

        // Create notification for another user (HR)
        $notif2 = HrmsNotification::create([
            'id' => Str::uuid()->toString(),
            'type' => HrmsGeneralNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $this->hrUser->id,
            'company_id' => $this->veCompany->id,
            'category' => 'Payroll',
            'channel' => 'in_app',
            'data' => ['title' => 'Confidential Payroll Alert', 'body' => 'Confidential figures for HR.'],
            'delivery_status' => 'delivered',
        ]);

        // Employee ESS visits /notifications
        $response = $this->actingAs($this->employeeUser)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->get(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertSee('Own Leave Approved');
        $response->assertDontSee('Confidential Payroll Alert');

        // Employee cannot mark other user's notification as read
        $markOther = $this->actingAs($this->employeeUser)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->post(route('notifications.mark-read', $notif2->id));

        $notif2->refresh();
        $this->assertNull($notif2->read_at); // Untouched
    }

    public function test_cross_company_notification_isolation(): void
    {
        // Delivery log in VRE
        $vreLog = NotificationDeliveryLog::create([
            'company_id' => $this->vreCompany->id,
            'channel' => 'sms',
            'recipient_address' => '+91 9988776655',
            'status' => 'failed',
            'provider_name' => 'mock_sms',
            'failure_reason' => 'VRE Delivery Gateway Error',
            'attempt_number' => 1,
            'dispatched_at' => now(),
        ]);

        // VE HR attempts to retry VRE log -> Must be blocked (404 via BelongsToCompany scoping or 403)
        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->post(route('admin.notifications.logs.retry', $vreLog->id));

        $this->assertTrue(in_array($response->status(), [403, 404]));
    }

    public function test_rbac_controls_on_admin_notification_endpoints(): void
    {
        // 1. Employee ESS is forbidden
        $essResp = $this->actingAs($this->employeeUser)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->get(route('admin.notifications.dashboard'));
        $essResp->assertStatus(403);

        // 2. Manager MSS is forbidden
        $mssResp = $this->actingAs($this->managerUser)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->get(route('admin.notifications.dashboard'));
        $mssResp->assertStatus(403);

        // 3. HR Executive is permitted
        $hrResp = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->get(route('admin.notifications.dashboard'));
        $hrResp->assertStatus(200);

        // 4. HO Admin is permitted
        $hoResp = $this->actingAs($this->hoAdmin)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->get(route('admin.notifications.dashboard'));
        $hoResp->assertStatus(200);
    }

    public function test_admin_can_update_template_and_preview(): void
    {
        $template = NotificationTemplate::where('code', 'ONBOARDING_WELCOME')
            ->where('channel', 'in_app')
            ->first();

        $this->assertNotNull($template);

        // Edit template
        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->put(route('admin.notifications.templates.update', $template->id), [
                'subject' => 'Updated Welcome to {company_name}',
                'body_template' => 'Hello {employee_name}, your journey begins here at {company_name}.',
                'action_url' => '/dashboard',
                'is_mandatory' => 1,
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('admin.notifications.templates.index'));
        $template->refresh();

        $this->assertEquals('Updated Welcome to {company_name}', $template->subject);
        $this->assertTrue($template->is_mandatory);

        // Preview template
        $previewResp = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->get(route('admin.notifications.templates.preview', $template->id));

        $previewResp->assertStatus(200);
        $previewResp->assertSee('Updated Welcome to');
    }

    public function test_admin_can_update_channel_settings(): void
    {
        $channel = NotificationChannelConfiguration::where('channel', 'sms')
            ->where('company_id', $this->veCompany->id)
            ->first();

        $this->assertNotNull($channel);

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->put(route('admin.notifications.channels.update', $channel->id), [
                'provider_name' => 'mock',
                'is_mock' => 1,
                'is_enabled' => 1,
                'credentials' => '{"api_key": "test-mock-key"}',
            ]);

        $response->assertRedirect();
        $channel->refresh();

        $this->assertEquals('mock', $channel->provider_name);
        $this->assertTrue($channel->is_mock);
        $this->assertTrue($channel->is_enabled);
    }
}
