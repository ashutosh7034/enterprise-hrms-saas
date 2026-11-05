<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use App\Services\CompanyContextService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class WorkforceDirectoryActionConsolidationTest extends TestCase
{
    protected User $hrUser;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hrUser = User::where('email', 'hr@vishal.com')->firstOrFail();
        $this->company = Company::where('code', 'VE')->firstOrFail();

        app(CompanyContextService::class)->setActiveCompanyId($this->company->id);
    }

    public function test_workforce_directory_loads_with_consolidated_header_actions(): void
    {
        $response = $this->actingAs($this->hrUser)->get(route('employees.index'));

        $response->assertOk();
        $response->assertSee('Workforce Master Directory');
        $response->assertSee('Add Employee');
        $response->assertSee('Bulk Import');
        $response->assertSee('Export to CSV');
        $response->assertSee('Export to Excel');
        $response->assertSee('Employee Code Settings');
    }

    public function test_export_csv_streams_valid_csv(): void
    {
        $response = $this->actingAs($this->hrUser)->get(route('employees.export', ['format' => 'csv']));

        $response->assertOk();
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
    }

    public function test_export_excel_streams_valid_xlsx(): void
    {
        $response = $this->actingAs($this->hrUser)->get(route('employees.export', ['format' => 'xlsx']));

        $response->assertOk();
        $this->assertTrue(
            str_contains($response->headers->get('content-type'), 'openxmlformats') ||
            str_contains($response->headers->get('content-disposition'), '.xlsx')
        );
    }

    public function test_bulk_import_template_downloads_csv_and_xlsx(): void
    {
        $responseCsv = $this->actingAs($this->hrUser)->get(route('employees.bulk-import.template', 'csv'));
        $responseCsv->assertOk();
        $this->assertTrue(str_contains($responseCsv->headers->get('content-type'), 'text/csv'));

        $responseXlsx = $this->actingAs($this->hrUser)->get(route('employees.bulk-import.template', 'xlsx'));
        $responseXlsx->assertOk();
        $this->assertTrue(
            str_contains($responseXlsx->headers->get('content-type'), 'openxmlformats') ||
            str_contains($responseXlsx->headers->get('content-disposition'), '.xlsx')
        );
    }

    public function test_bulk_import_validation_and_confirmation(): void
    {
        $location = Location::where('company_id', $this->company->id)->firstOrFail();
        $dept = Department::where('company_id', $this->company->id)->firstOrFail();
        $desig = Designation::firstOrFail();

        $uniquePhone1 = '9' . rand(100000000, 999999999);
        $uniquePhone2 = '9' . rand(100000000, 999999999);
        $uniqueEmail1 = 'import.test.' . uniqid() . '@example.com';

        // Row 1: Valid
        // Row 2: Underage (Age < 18)
        $csvContent = "First Name,Middle Name,Last Name,Gender,Date of Birth,Phone,Email,Date of Joining,Branch,Department,Designation\n" .
            "Rohan,K,Patil,Male,1992-06-15,{$uniquePhone1},{$uniqueEmail1}," . date('Y-m-d') . ",{$location->name},{$dept->name},{$desig->title}\n" .
            "Underage,Boy,Singh,Male," . date('Y-m-d', strtotime('-15 years')) . ",{$uniquePhone2},underage@example.com," . date('Y-m-d') . ",{$location->name},{$dept->name},{$desig->title}\n";

        $file = UploadedFile::fake()->createWithContent('import_test.csv', $csvContent);

        // Step A: Validate File
        $validateResp = $this->actingAs($this->hrUser)->postJson(route('employees.bulk-import.validate'), [
            'file' => $file,
        ]);

        $validateResp->assertOk();
        $data = $validateResp->json();

        $this->assertTrue($data['success']);
        $this->assertEquals(2, $data['total_rows']);
        $this->assertEquals(1, $data['valid_count']);
        $this->assertEquals(1, $data['invalid_count']);
        $this->assertNotEmpty($data['batch_token']);

        // Step B: Confirm Import
        $confirmResp = $this->actingAs($this->hrUser)->postJson(route('employees.bulk-import.confirm'), [
            'batch_token' => $data['batch_token'],
        ]);

        $confirmResp->assertOk();
        $confirmData = $confirmResp->json();
        $this->assertTrue($confirmData['success']);
        $this->assertEquals(1, $confirmData['imported_count']);

        $this->assertDatabaseHas('employees', [
            'company_id' => $this->company->id,
            'phone' => $uniquePhone1,
            'email' => $uniqueEmail1,
        ]);
    }

    public function test_employee_code_settings_page_loads_with_both_tabs(): void
    {
        $response = $this->actingAs($this->hrUser)->get(route('admin.employee-codes.settings'));

        $response->assertOk();
        $response->assertSee('Employee Code Settings');
        $response->assertSee('Format & Sequence Configuration', false);
        $response->assertSee('Employee Code Master List');
    }

    public function test_existing_code_config_and_master_routes_remain_functional(): void
    {
        $responseIndex = $this->actingAs($this->hrUser)->get(route('admin.employee-codes.index'));
        $responseIndex->assertOk();

        $responseMaster = $this->actingAs($this->hrUser)->get(route('admin.employee-codes.master'));
        $responseMaster->assertOk();

        $responseExport = $this->actingAs($this->hrUser)->get(route('admin.employee-codes.export'));
        $responseExport->assertOk();
    }
}
