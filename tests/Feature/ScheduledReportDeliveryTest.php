<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\ScheduledReport;
use App\Models\ScheduledReportLog;
use App\Models\User;
use App\Services\CompanyContextService;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ScheduledReportDeliveryTest extends TestCase
{
    protected Company $company;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::where('code', 'VE')->first() ?? Company::first();
        $adminRole = Role::where('name', 'ho_admin')->first();
        $this->adminUser = User::where('role_id', $adminRole->id)->first();

        app(CompanyContextService::class)->setActiveCompanyId($this->company->id);
    }

    public function test_admin_can_view_schedules_console(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/reports/schedules');

        $response->assertStatus(200);
        $response->assertSee('Scheduled Reports Manager');
    }

    public function test_admin_can_create_scheduled_report(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/admin/reports/schedules', [
            'name' => 'Weekly Attendance Summary Auto-Dispatch',
            'report_type' => 'MONTHLY_ATTENDANCE_SUMMARY',
            'frequency' => 'Weekly',
            'export_format' => 'csv',
            'recipients' => 'hr@vishal.internal, admin@vishal.internal',
        ]);

        $response->assertRedirect('/admin/reports/schedules');

        $this->assertDatabaseHas('scheduled_reports', [
            'company_id' => $this->company->id,
            'name' => 'Weekly Attendance Summary Auto-Dispatch',
            'frequency' => 'Weekly',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_toggle_schedule_active_status(): void
    {
        $schedule = ScheduledReport::create([
            'company_id' => $this->company->id,
            'name' => 'Toggle Test Schedule',
            'report_type' => 'MASTER_EMPLOYEE',
            'frequency' => 'Monthly',
            'export_format' => 'csv',
            'recipients_json' => [$this->adminUser->email],
            'is_active' => true,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->post("/admin/reports/schedules/{$schedule->id}/toggle");
        $response->assertStatus(302);

        $schedule->refresh();
        $this->assertFalse($schedule->is_active);
    }

    public function test_artisan_command_dispatches_due_reports_and_logs(): void
    {
        $schedule = ScheduledReport::create([
            'company_id' => $this->company->id,
            'name' => 'Artisan Dispatch Test',
            'report_type' => 'MASTER_EMPLOYEE',
            'frequency' => 'Daily',
            'export_format' => 'csv',
            'recipients_json' => [$this->adminUser->email],
            'is_active' => true,
            'next_run_at' => now()->subMinute(),
            'created_by' => $this->adminUser->id,
        ]);

        $exitCode = Artisan::call('reports:dispatch-scheduled', [
            '--report-id' => $schedule->id,
            '--force' => true,
        ]);

        $this->assertEquals(0, $exitCode);

        // Check scheduled report log created
        $this->assertDatabaseHas('scheduled_report_logs', [
            'scheduled_report_id' => $schedule->id,
            'status' => 'Success',
            'recipients_count' => 1,
        ]);

        // Check schedule was updated with new next_run_at
        $schedule->refresh();
        $this->assertNotNull($schedule->last_run_at);
        $this->assertTrue($schedule->next_run_at->isFuture());
    }

    public function test_admin_can_soft_delete_schedule(): void
    {
        $schedule = ScheduledReport::create([
            'company_id' => $this->company->id,
            'name' => 'Delete Test Schedule',
            'report_type' => 'MASTER_EMPLOYEE',
            'frequency' => 'Daily',
            'export_format' => 'csv',
            'recipients_json' => [$this->adminUser->email],
            'is_active' => true,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->delete("/admin/reports/schedules/{$schedule->id}");
        $response->assertStatus(302);

        $this->assertSoftDeleted('scheduled_reports', ['id' => $schedule->id]);
    }
}

