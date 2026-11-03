<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Company;
use App\Models\Contractor;
use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use App\Services\CompanyContextService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityAuditRegressionTest extends TestCase
{
    protected User $hrVishal;
    protected User $hoAdmin;
    protected Company $vishal;
    protected Company $vrishal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hrVishal = User::where('email', 'hr@vishal.com')->firstOrFail();
        $this->hoAdmin = User::where('email', 'ho_admin@enterprise.com')->firstOrFail();
        $this->vishal = Company::where('code', 'VE')->firstOrFail();
        $this->vrishal = Company::where('code', 'VRE')->firstOrFail();

        app(CompanyContextService::class)->setActiveCompanyId($this->vishal->id);
    }

    public function test_hr_executive_cannot_create_or_modify_legal_companies(): void
    {
        // Attempt POST to create company as HR Executive
        $respCreate = $this->actingAs($this->hrVishal)->post(route('companies.store'), [
            'name' => 'Unauthorized Co',
            'code' => 'UNAUTH',
            'status' => 'Active',
        ]);
        $respCreate->assertStatus(403);

        // Attempt PUT to edit company as HR Executive
        $respUpdate = $this->actingAs($this->hrVishal)->put(route('companies.update', $this->vishal), [
            'name' => 'Hacked Company Name',
            'code' => 'VE',
            'status' => 'Active',
        ]);
        $respUpdate->assertStatus(403);
    }

    public function test_cross_company_manipulated_id_attacks_are_blocked(): void
    {
        // Fetch Location belonging to Vrishal Eng.
        $vrishalLocation = Location::withoutGlobalScopes()
            ->where('company_id', $this->vrishal->id)
            ->firstOrFail();

        // HR of Vishal attempts PUT to edit Vrishal's location
        $respLocUpdate = $this->actingAs($this->hrVishal)->put(route('locations.update', $vrishalLocation), [
            'name' => 'Hijacked Location',
            'code' => $vrishalLocation->code,
            'state' => 'Maharashtra',
            'status' => 'Active',
        ]);
        $this->assertTrue(in_array($respLocUpdate->status(), [403, 404]));

        // HR of Vishal attempts DELETE on Vrishal's location
        $respLocDelete = $this->actingAs($this->hrVishal)->delete(route('locations.destroy', $vrishalLocation));
        $this->assertTrue(in_array($respLocDelete->status(), [403, 404]));

        // Fetch Department belonging to Vrishal Eng.
        $vrishalDept = Department::withoutGlobalScopes()
            ->where('company_id', $this->vrishal->id)
            ->firstOrFail();

        $respDeptUpdate = $this->actingAs($this->hrVishal)->put(route('departments.update', $vrishalDept), [
            'name' => 'Hijacked Department',
            'code' => $vrishalDept->code,
            'status' => 'Active',
        ]);
        $this->assertTrue(in_array($respDeptUpdate->status(), [403, 404]));

        // Fetch Category belonging to Vrishal Eng.
        $vrishalCat = Category::withoutGlobalScopes()
            ->where('company_id', $this->vrishal->id)
            ->firstOrFail();

        $respCatUpdate = $this->actingAs($this->hrVishal)->put(route('categories.update', $vrishalCat), [
            'name' => 'Hijacked Category',
            'code' => $vrishalCat->code,
            'notice_period_days' => 15,
            'status' => 'Active',
        ]);
        $this->assertTrue(in_array($respCatUpdate->status(), [403, 404]));

        // Fetch Contractor belonging to Vrishal Eng.
        $vrishalContractor = Contractor::withoutGlobalScopes()
            ->where('company_id', $this->vrishal->id)
            ->firstOrFail();

        $respConUpdate = $this->actingAs($this->hrVishal)->put(route('contractors.update', $vrishalContractor), [
            'agency_name' => 'Hijacked Agency',
            'code' => $vrishalContractor->code,
            'contact_person' => 'Nobody',
            'phone' => '+91 99999 99999',
            'status' => 'Active',
        ]);
        $this->assertTrue(in_array($respConUpdate->status(), [403, 404]));
    }

    public function test_designation_creation_with_cross_company_department_is_rejected(): void
    {
        // Department from Vrishal Eng.
        $vrishalDept = Department::withoutGlobalScopes()
            ->where('company_id', $this->vrishal->id)
            ->firstOrFail();

        // While active company is Vishal Enterprise, attempt to attach designation to Vrishal's department
        $response = $this->actingAs($this->hrVishal)->post(route('designations.store'), [
            'department_id' => $vrishalDept->id,
            'title' => 'Injected Title',
            'code' => 'DES-INJECT',
            'status' => 'Active',
        ]);

        $response->assertSessionHasErrors('department_id');
    }

    public function test_raw_database_immutability_triggers_block_tampering(): void
    {
        $log = AuditLog::latest('id')->firstOrFail();

        // Raw SQL UPDATE must be rejected by MySQL trigger
        try {
            DB::table('audit_logs')->where('id', $log->id)->update(['action' => 'RAW_TAMPER']);
            $this->fail('Expected MySQL trigger to block raw UPDATE on audit_logs.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('Audit records are immutable and cannot be updated', $e->getMessage());
        }

        // Raw SQL DELETE must be rejected by MySQL trigger
        try {
            DB::table('audit_logs')->where('id', $log->id)->delete();
            $this->fail('Expected MySQL trigger to block raw DELETE on audit_logs.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('Audit records are permanent and cannot be deleted', $e->getMessage());
        }
    }

    public function test_demo_credentials_hidden_in_production(): void
    {
        $this->app['env'] = 'production';
        config(['app.env' => 'production']);

        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertDontSee('Quick Switch Role Credentials');
    }
}
