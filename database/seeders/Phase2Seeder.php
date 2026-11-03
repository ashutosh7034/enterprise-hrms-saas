<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Category;
use App\Models\CentralBlacklistRecord;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeGatePassHistory;
use App\Models\FineMaster;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;

class Phase2Seeder extends Seeder
{
    public function run(): void
    {
        $hoAdmin = User::where('email', 'ho_admin@enterprise.com')->first() ?? User::first();
        $veCompany = Company::where('code', 'VE')->first();
        $vreCompany = Company::where('code', 'VRE')->first();

        if (!$veCompany || !$vreCompany) {
            return;
        }

        // 1. Asset Categories
        $laptopCat = AssetCategory::firstOrCreate(
            ['code' => 'IT-LAP'],
            ['name' => 'Laptops & IT Hardware', 'is_returnable' => true, 'description' => 'Notebooks, monitors, and computing accessories']
        );

        $toolCat = AssetCategory::firstOrCreate(
            ['code' => 'IND-TOOL'],
            ['name' => 'Industrial Tools & Instruments', 'is_returnable' => true, 'description' => 'Precision measuring instruments, drills, and power tools']
        );

        $ppeCat = AssetCategory::firstOrCreate(
            ['code' => 'PPE-GEAR'],
            ['name' => 'Safety & PPE Equipment', 'is_returnable' => true, 'description' => 'Harnesses, safety helmets, and fall protection devices']
        );

        $radioCat = AssetCategory::firstOrCreate(
            ['code' => 'COM-RAD'],
            ['name' => 'Communication Handsets', 'is_returnable' => true, 'description' => 'Walkie-talkies and wireless radio sets']
        );

        // 2. Fine Master Policies
        FineMaster::firstOrCreate(
            ['company_id' => $veCompany->id, 'fine_code' => 'VE-FINE-01'],
            [
                'fine_name' => 'Laptop Hardware / Screen Damage',
                'category' => 'Asset Damage',
                'asset_category_id' => $laptopCat->id,
                'default_amount' => 3500.00,
                'effective_date' => '2026-01-01',
                'status' => 'Active',
                'remarks' => 'Assessed for physical screen cracks, liquid spills, or casing fractures due to negligence.',
            ]
        );

        FineMaster::firstOrCreate(
            ['company_id' => $veCompany->id, 'fine_code' => 'VE-FINE-02'],
            [
                'fine_name' => 'Safety Gear / Fall Harness Loss',
                'category' => 'Asset Loss',
                'asset_category_id' => $ppeCat->id,
                'default_amount' => 1200.00,
                'effective_date' => '2026-01-01',
                'status' => 'Active',
                'remarks' => 'Applicable for lost safety equipment at construction or ground staff plant locations.',
            ]
        );

        FineMaster::firstOrCreate(
            ['company_id' => $vreCompany->id, 'fine_code' => 'VRE-FINE-01'],
            [
                'fine_name' => 'Industrial Precision Tool Misuse / Damage',
                'category' => 'Asset Damage',
                'asset_category_id' => $toolCat->id,
                'default_amount' => 4500.00,
                'effective_date' => '2026-01-01',
                'status' => 'Active',
                'remarks' => 'Covers repair and calibration fees for dropped or damaged factory instruments.',
            ]
        );

        // 3. Central Cross-Company Blacklist
        $rameshAadhaar = '123456789012';
        CentralBlacklistRecord::firstOrCreate(
            ['pan_number' => 'ABCDE1234F'],
            [
                'name' => 'Ramesh Kumar',
                'date_of_birth' => '1988-06-15',
                'aadhaar_hash' => hash('sha256', $rameshAadhaar),
                'aadhaar_masked' => 'XXXX-XXXX-9012',
                'phone' => '9876543210',
                'previous_employee_code' => 'VE-OLD-089',
                'previous_company_id' => $veCompany->id,
                'reason_category' => 'Serious Misconduct',
                'evidence_notes' => 'Caught deliberately tampering with safety interlocks on plant machinery. Formal enquiry conducted. Terminated immediately.',
                'approval_status' => 'Approved',
                'requested_by' => $hoAdmin->id,
                'approved_by' => $hoAdmin->id,
                'approved_at' => now()->subMonths(2),
                'is_active' => true,
            ]
        );

        $sureshAadhaar = '987654321098';
        CentralBlacklistRecord::firstOrCreate(
            ['pan_number' => 'XYZPQ5678R'],
            [
                'name' => 'Suresh Verma',
                'date_of_birth' => '1992-09-21',
                'aadhaar_hash' => hash('sha256', $sureshAadhaar),
                'aadhaar_masked' => 'XXXX-XXXX-1098',
                'phone' => '9822334455',
                'previous_employee_code' => 'VRE-OLD-014',
                'previous_company_id' => $vreCompany->id,
                'reason_category' => 'Financial / Asset Misconduct',
                'evidence_notes' => 'Absconded with company-issued Dell workstation and laser testing tools worth ₹85,000. Formal police FIR registered.',
                'approval_status' => 'Approved',
                'requested_by' => $hoAdmin->id,
                'approved_by' => $hoAdmin->id,
                'approved_at' => now()->subMonth(),
                'is_active' => true,
            ]
        );

        // 4. Assets for VE
        $veLoc = Location::withoutGlobalScopes()->where('company_id', $veCompany->id)->first();
        $veAsset1 = Asset::firstOrCreate(
            ['asset_code' => 'VE-AST-001'],
            [
                'company_id' => $veCompany->id,
                'location_id' => $veLoc?->id,
                'asset_category_id' => $laptopCat->id,
                'name' => 'Dell Latitude 5420 i7 16GB',
                'asset_tag' => 'TAG-VE-5420',
                'serial_number' => 'SN-DL-88392',
                'purchase_date' => '2025-06-15',
                'purchase_cost' => 65000.00,
                'is_returnable' => true,
                'condition' => 'Good',
                'status' => 'Available',
                'remarks' => 'Standard engineering laptop with charger and bag.',
            ]
        );

        $veAsset2 = Asset::firstOrCreate(
            ['asset_code' => 'VE-AST-002'],
            [
                'company_id' => $veCompany->id,
                'location_id' => $veLoc?->id,
                'asset_category_id' => $radioCat->id,
                'name' => 'Motorola GP328 Two-Way Radio',
                'asset_tag' => 'TAG-VE-RAD-02',
                'serial_number' => 'SN-MOT-9921',
                'purchase_date' => '2025-08-10',
                'purchase_cost' => 12500.00,
                'is_returnable' => true,
                'condition' => 'Brand New',
                'status' => 'Available',
                'remarks' => 'Includes charging dock and belt clip.',
            ]
        );

        // Assets for VRE
        $vreLoc = Location::withoutGlobalScopes()->where('company_id', $vreCompany->id)->first();
        Asset::firstOrCreate(
            ['asset_code' => 'VRE-AST-001'],
            [
                'company_id' => $vreCompany->id,
                'location_id' => $vreLoc?->id,
                'asset_category_id' => $laptopCat->id,
                'name' => 'HP ProBook 450 G8',
                'asset_tag' => 'TAG-VRE-450',
                'serial_number' => 'SN-HP-33912',
                'purchase_date' => '2025-09-01',
                'purchase_cost' => 58000.00,
                'is_returnable' => true,
                'condition' => 'Good',
                'status' => 'Available',
                'remarks' => 'Assigned for manufacturing plant dispatch.',
            ]
        );

        // 5. Sample Workforce Employees
        $veDept = Department::withoutGlobalScopes()->where('company_id', $veCompany->id)->first();
        $veDesig = Designation::withoutGlobalScopes()->whereHas('department', fn($q) => $q->where('company_id', $veCompany->id))->first();
        $veCategory = Category::withoutGlobalScopes()->where('company_id', $veCompany->id)->first();

        if ($veDept && $veDesig && $veCategory) {
            $amit = Employee::firstOrCreate(
                ['employee_code' => 'VE-EMP-1001'],
                [
                    'company_id' => $veCompany->id,
                    'first_name' => 'Amit',
                    'last_name' => 'Sharma',
                    'gender' => 'Male',
                    'date_of_birth' => '1990-04-12',
                    'phone' => '9811223344',
                    'email' => 'amit.sharma@vishal.internal',
                    'date_of_joining' => '2025-01-15',
                    'location_id' => $veLoc?->id,
                    'department_id' => $veDept->id,
                    'designation_id' => $veDesig->id,
                    'category_id' => $veCategory->id,
                    'employment_channel' => 'Direct',
                    'gate_pass_number' => 'VE-GP-4001',
                    'pan_number' => 'BKLPS9012K',
                    'aadhaar_hash' => hash('sha256', '555566667777'),
                    'aadhaar_masked' => 'XXXX-XXXX-7777',
                    'address' => 'Plot 10, Ganesh Nagar, Bhosari, Pune',
                    'status' => 'Active',
                    'ekyc_status' => 'Verified',
                ]
            );

            EmployeeGatePassHistory::firstOrCreate(
                ['employee_id' => $amit->id, 'new_gate_pass' => 'VE-GP-4001'],
                [
                    'previous_gate_pass' => null,
                    'reason' => 'Initial workforce gate pass allocation on onboarding',
                    'changed_by' => $hoAdmin->id,
                ]
            );

            // Second employee with active grace period
            $priya = Employee::firstOrCreate(
                ['employee_code' => 'VE-EMP-1002'],
                [
                    'company_id' => $veCompany->id,
                    'first_name' => 'Priya',
                    'last_name' => 'Nair',
                    'gender' => 'Female',
                    'date_of_birth' => '1995-11-03',
                    'phone' => '9822998877',
                    'email' => 'priya.nair@vishal.internal',
                    'date_of_joining' => '2026-02-01',
                    'location_id' => $veLoc?->id,
                    'department_id' => $veDept->id,
                    'designation_id' => $veDesig->id,
                    'category_id' => $veCategory->id,
                    'employment_channel' => 'Direct',
                    'gate_pass_number' => 'VE-GP-4002',
                    'pan_number' => 'CPQNM4321L',
                    'aadhaar_hash' => hash('sha256', '888899990000'),
                    'aadhaar_masked' => 'XXXX-XXXX-0000',
                    'address' => 'Flat 402, Sai Residency, Chakan, Pune',
                    'status' => 'Active',
                    'ekyc_status' => 'Pending',
                    'ekyc_resubmission_deadline' => now()->addDays(14)->toDateString(),
                ]
            );

            EmployeeGatePassHistory::firstOrCreate(
                ['employee_id' => $priya->id, 'new_gate_pass' => 'VE-GP-4002'],
                [
                    'previous_gate_pass' => null,
                    'reason' => 'Initial gate pass allocation',
                    'changed_by' => $hoAdmin->id,
                ]
            );
        }
    }
}
