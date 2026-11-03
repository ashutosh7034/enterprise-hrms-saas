<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetAllocation;
use App\Models\AssetDamageAssessment;
use App\Models\Company;
use App\Models\Employee;
use App\Models\FineMaster;
use App\Models\FineRecoveryRecord;
use App\Models\User;
use Tests\TestCase;

class FineAndRecoveryTest extends TestCase
{
    protected User $hrUser;
    protected User $hoAdmin;
    protected Company $veCompany;
    protected Employee $employee;
    protected Asset $asset;
    protected AssetAllocation $allocation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hrUser = User::where('email', 'hr@vishal.com')->first();
        $this->hoAdmin = User::where('email', 'ho_admin@enterprise.com')->first();
        $this->veCompany = Company::where('code', 'VE')->first();
        
        $this->employee = Employee::withoutGlobalScopes()->where('company_id', $this->veCompany->id)->first();
        if (!$this->employee) {
            $loc = \App\Models\Location::withoutGlobalScopes()->where('company_id', $this->veCompany->id)->first();
            $dept = \App\Models\Department::withoutGlobalScopes()->where('company_id', $this->veCompany->id)->first();
            $desig = \App\Models\Designation::withoutGlobalScopes()->first();
            $cat = \App\Models\Category::withoutGlobalScopes()->where('company_id', $this->veCompany->id)->first();
            $this->employee = Employee::create([
                'company_id' => $this->veCompany->id,
                'employee_code' => 'VE-EMP-1001',
                'first_name' => 'Amit',
                'last_name' => 'Sharma',
                'gender' => 'Male',
                'date_of_birth' => '1990-04-12',
                'phone' => '9811223344',
                'date_of_joining' => '2025-01-15',
                'location_id' => $loc->id,
                'department_id' => $dept->id,
                'designation_id' => $desig->id,
                'category_id' => $cat->id,
                'status' => 'Active',
                'ekyc_status' => 'Pending',
            ]);
        }

        $this->asset = Asset::withoutGlobalScopes()->where('company_id', $this->veCompany->id)->first();
        if (!$this->asset) {
            $cat = \App\Models\AssetCategory::firstOrCreate(['code' => 'IT-LAP'], ['name' => 'Laptops']);
            $this->asset = Asset::create([
                'company_id' => $this->veCompany->id,
                'asset_code' => 'VE-AST-001',
                'name' => 'Dell Latitude 5420',
                'asset_category_id' => $cat->id,
                'purchase_cost' => 65000.00,
                'condition' => 'Good',
                'status' => 'Available',
            ]);
        }

        $this->allocation = AssetAllocation::firstOrCreate(
            ['asset_id' => $this->asset->id, 'employee_id' => $this->employee->id],
            [
                'company_id' => $this->veCompany->id,
                'allocated_date' => now()->subMonths(2)->toDateString(),
                'condition_at_issue' => 'Good',
                'status' => 'Active',
            ]
        );
    }

    public function test_can_create_fine_master_policy(): void
    {
        $code = 'FINE-TST-' . mt_rand(100, 999);

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->post(route('fine-masters.store'), [
                'fine_code' => $code,
                'fine_name' => 'Laser Meter Glass Breakage',
                'category' => 'Asset Damage',
                'default_amount' => 2800.00,
                'effective_date' => now()->toDateString(),
                'status' => 'Active',
                'remarks' => 'Test fine policy',
            ]);

        $response->assertRedirect(route('fine-masters.index'));

        $fine = FineMaster::where('fine_code', $code)->first();
        $this->assertNotNull($fine);
        $this->assertEquals(2800.00, $fine->default_amount);
    }

    public function test_can_log_damage_assessment_and_approve_generates_installments(): void
    {
        // 1. Log assessment
        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->post(route('damage-assessments.store'), [
                'asset_allocation_id' => $this->allocation->id,
                'assessed_amount' => 6000.00,
                'depreciation_strategy' => 'Straight-Line',
                'recovery_method' => 'Payroll Queue',
                'installment_count' => 3,
                'condition_evidence' => 'Cracked LCD matrix on field laptop',
                'investigation_notes' => 'Dropped on concrete slab at plant floor',
            ]);

        $response->assertRedirect(route('damage-assessments.index'));

        $assessment = AssetDamageAssessment::where('asset_allocation_id', $this->allocation->id)->latest('id')->first();
        $this->assertNotNull($assessment);
        $this->assertEquals('Pending Approval', $assessment->status);
        $this->assertEquals(6000.00, $assessment->assessed_amount);

        // 2. Approve assessment
        $approveResponse = $this->actingAs($this->hoAdmin)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->post(route('damage-assessments.approve', $assessment->id));

        $approveResponse->assertRedirect();

        $assessment->refresh();
        $this->assertEquals('Approved', $assessment->status);

        // 3. Verify 3 installments were created in fine_recovery_records
        $installments = FineRecoveryRecord::where('damage_assessment_id', $assessment->id)->orderBy('installment_number')->get();
        $this->assertCount(3, $installments);
        $this->assertEquals(2000.00, $installments[0]->amount);
        $this->assertEquals('Queued for Phase 5 Payroll', $installments[0]->status);
        $this->assertEquals('Payroll Deduction Queue', $installments[0]->payment_channel);
    }

    public function test_direct_payment_recording_settles_installment(): void
    {
        $assessment = AssetDamageAssessment::create([
            'asset_allocation_id' => $this->allocation->id,
            'company_id' => $this->veCompany->id,
            'assessed_amount' => 1500.00,
            'net_recovery_amount' => 1500.00,
            'depreciated_value' => 2000.00,
            'status' => 'Approved',
            'assessed_by' => $this->hrUser->id,
            'recovery_method' => 'Direct Payment',
            'installment_count' => 1,
        ]);

        $record = FineRecoveryRecord::create([
            'damage_assessment_id' => $assessment->id,
            'employee_id' => $this->employee->id,
            'company_id' => $this->veCompany->id,
            'installment_number' => 1,
            'amount' => 1500.00,
            'payment_channel' => 'Direct Payment',
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->veCompany->id])
            ->post(route('fine-recovery.payment', $record->id), [
                'amount' => 1500.00,
                'payment_channel' => 'Direct Payment',
                'receipt_reference' => 'REC-2026-00441',
                'remarks' => 'Cash received at HR desk',
            ]);

        $response->assertRedirect();

        $record->refresh();
        $this->assertEquals('Paid', $record->status);
        $this->assertEquals('REC-2026-00441', $record->receipt_reference);
        $this->assertNotNull($record->paid_date);
    }

    public function test_ho_admin_can_apply_waiver(): void
    {
        $assessment = AssetDamageAssessment::create([
            'asset_allocation_id' => $this->allocation->id,
            'company_id' => $this->veCompany->id,
            'assessed_amount' => 4000.00,
            'net_recovery_amount' => 4000.00,
            'depreciated_value' => 5000.00,
            'status' => 'Approved',
            'assessed_by' => $this->hrUser->id,
            'recovery_method' => 'Direct Payment',
            'installment_count' => 1,
        ]);

        $response = $this->actingAs($this->hoAdmin)
            ->post(route('damage-assessments.waiver', $assessment->id), [
                'waived_amount' => 4000.00,
                'waiver_reason' => 'Management waiver granted due to sudden mechanical surge fault beyond operator control.',
            ]);

        $response->assertRedirect();

        $assessment->refresh();
        $this->assertEquals('Waived', $assessment->status);
        $this->assertEquals(4000.00, $assessment->waived_amount);
        $this->assertEquals(0.00, $assessment->net_recovery_amount);
        $this->assertEquals($this->hoAdmin->id, $assessment->waiver_approved_by);
    }
}
