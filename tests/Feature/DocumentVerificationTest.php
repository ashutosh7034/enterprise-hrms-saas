<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeDocumentVersion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentVerificationTest extends TestCase
{
    protected User $hrUser;
    protected Company $company;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->hrUser = User::where('email', 'hr@vishal.com')->first();
        $this->company = Company::where('code', 'VE')->first();
        $this->employee = Employee::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
        if (!$this->employee) {
            $loc = \App\Models\Location::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
            $dept = \App\Models\Department::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
            $desig = \App\Models\Designation::withoutGlobalScopes()->first();
            $cat = \App\Models\Category::withoutGlobalScopes()->where('company_id', $this->company->id)->first();
            $this->employee = Employee::create([
                'company_id' => $this->company->id,
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
        EmployeeDocument::where('employee_id', $this->employee->id)->forceDelete();
    }

    public function test_can_upload_document_and_version_is_created(): void
    {
        $fakeFile = UploadedFile::fake()->create('pan_card.pdf', 300, 'application/pdf');

        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('documents.store', $this->employee->id), [
                'document_type' => 'PAN Card',
                'document_number' => 'DOC-PAN-1001',
                'file' => $fakeFile,
                'remarks' => 'Scanned official PAN card',
            ]);

        $response->assertRedirect();

        $doc = EmployeeDocument::where('employee_id', $this->employee->id)
            ->where('document_type', 'PAN Card')
            ->first();

        $this->assertNotNull($doc);
        $this->assertEquals(1, $doc->current_version);
        $this->assertEquals('Pending', $doc->verification_status);
        Storage::disk('local')->assertExists($doc->file_path);

        $version = EmployeeDocumentVersion::where('employee_document_id', $doc->id)->first();
        $this->assertNotNull($version);
        $this->assertEquals(1, $version->version_number);
    }

    public function test_subsequent_upload_increments_version_number(): void
    {
        $fakeFile1 = UploadedFile::fake()->create('aadhaar_v1.pdf', 200, 'application/pdf');
        $fakeFile2 = UploadedFile::fake()->create('aadhaar_v2.pdf', 250, 'application/pdf');

        $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('documents.store', $this->employee->id), [
                'document_type' => 'Aadhaar Card',
                'file' => $fakeFile1,
            ]);

        $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('documents.store', $this->employee->id), [
                'document_type' => 'Aadhaar Card',
                'file' => $fakeFile2,
            ]);

        $doc = EmployeeDocument::where('employee_id', $this->employee->id)
            ->where('document_type', 'Aadhaar Card')
            ->first();

        $this->assertEquals(2, $doc->current_version);
        $this->assertEquals(2, $doc->versions()->count());
    }

    public function test_hr_can_verify_or_reject_document_with_reason(): void
    {
        $fakeFile = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('documents.store', $this->employee->id), [
                'document_type' => 'Experience Certificate',
                'file' => $fakeFile,
            ]);

        $doc = EmployeeDocument::where('employee_id', $this->employee->id)
            ->where('document_type', 'Experience Certificate')
            ->first();

        // 1. Verify
        $response = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('documents.verify', $doc->id), [
                'action' => 'verify',
            ]);

        $response->assertRedirect();
        $doc->refresh();
        $this->assertEquals('Verified', $doc->verification_status);

        // 2. Reject with reason
        $responseReject = $this->actingAs($this->hrUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('documents.verify', $doc->id), [
                'action' => 'reject',
                'rejection_reason' => 'Relieving date stamped is missing authorization seal',
            ]);

        $responseReject->assertRedirect();
        $doc->refresh();
        $this->assertEquals('Rejected', $doc->verification_status);
        $this->assertEquals('Relieving date stamped is missing authorization seal', $doc->rejection_reason);
    }
}
