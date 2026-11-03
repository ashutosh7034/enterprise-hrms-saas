<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\Contractor;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $hoAdminRole = Role::firstOrCreate(
            ['name' => 'ho_admin'],
            ['label' => 'HO Team Admin', 'description' => 'Full enterprise head office administrative privileges across all legal entities']
        );

        $hrRole = Role::firstOrCreate(
            ['name' => 'hr_executive'],
            ['label' => 'HR Executive', 'description' => 'Human resources management within assigned company scope']
        );

        $managerRole = Role::firstOrCreate(
            ['name' => 'manager_mss'],
            ['label' => 'Manager MSS', 'description' => 'Reporting supervisor and manager self-service oversight']
        );

        $employeeRole = Role::firstOrCreate(
            ['name' => 'employee_ess'],
            ['label' => 'Employee ESS', 'description' => 'Direct and contractor workforce employee self-service']
        );

        // 2. Legal Companies (Vishal Enterprise and Vrishal Eng. Pvt. Ltd.)
        $vishal = Company::firstOrCreate(
            ['code' => 'VE'],
            [
                'name' => 'Vishal Enterprise',
                'cin_number' => 'U28910MH2012PTC231456',
                'gstin' => '27AAACV1234F1Z1',
                'pan_number' => 'AAACV1234F',
                'address' => 'Plot No. 42, MIDC Bhosari, Pune, Maharashtra 411026',
                'phone' => '+91 20 2712 4000',
                'email' => 'contact@vishalenterprise.com',
                'status' => 'Active',
            ]
        );

        $vrishal = Company::firstOrCreate(
            ['code' => 'VRE'],
            [
                'name' => 'Vrishal Eng. Pvt. Ltd.',
                'cin_number' => 'U29253MH2018PTC310234',
                'gstin' => '27AABCV5678M1Z8',
                'pan_number' => 'AABCV5678M',
                'address' => 'Gate No. 89, Chakan Industrial Area Phase II, Pune, Maharashtra 410501',
                'phone' => '+91 2135 680000',
                'email' => 'info@vrishaleng.com',
                'status' => 'Active',
            ]
        );

        // 3. Locations for Vishal Enterprise
        $locVePlant = Location::withoutGlobalScopes()->firstOrCreate(
            ['company_id' => $vishal->id, 'code' => 'VE-PUN-01'],
            [
                'name' => 'Pune Bhosari Main Plant',
                'state' => 'Maharashtra',
                'address' => 'Plot 42, MIDC Bhosari, Pune 411026',
                'is_site' => false,
                'status' => 'Active',
            ]
        );

        $locVeChakan = Location::withoutGlobalScopes()->firstOrCreate(
            ['company_id' => $vishal->id, 'code' => 'VE-CHK-02'],
            [
                'name' => 'Chakan Machine Shop',
                'state' => 'Maharashtra',
                'address' => 'Sector 10, Chakan MIDC, Pune 410501',
                'is_site' => false,
                'status' => 'Active',
            ]
        );

        $locVeSite = Location::withoutGlobalScopes()->firstOrCreate(
            ['company_id' => $vishal->id, 'code' => 'VE-BAR-SITE'],
            [
                'name' => 'Baramati Ground Site',
                'state' => 'Maharashtra',
                'address' => 'Baramati Agro Industrial Corridor, Baramati',
                'is_site' => true, // Ground staff site!
                'status' => 'Active',
            ]
        );

        // Locations for Vrishal Eng.
        $locVrePlant = Location::withoutGlobalScopes()->firstOrCreate(
            ['company_id' => $vrishal->id, 'code' => 'VRE-CHK-01'],
            [
                'name' => 'Chakan Heavy Fabrication Plant',
                'state' => 'Maharashtra',
                'address' => 'Gate 89, Chakan Phase II, Pune',
                'is_site' => false,
                'status' => 'Active',
            ]
        );

        $locVreSite = Location::withoutGlobalScopes()->firstOrCreate(
            ['company_id' => $vrishal->id, 'code' => 'VRE-SAN-SITE'],
            [
                'name' => 'Sanand Turbine Project Site',
                'state' => 'Gujarat',
                'address' => 'GIDC Sanand II, Ahmedabad, Gujarat',
                'is_site' => true, // Ground staff site!
                'status' => 'Active',
            ]
        );

        // 4. Departments for Vishal Enterprise
        $deptVeOps = Department::withoutGlobalScopes()->firstOrCreate(
            ['company_id' => $vishal->id, 'code' => 'VE-OPS'],
            ['name' => 'Plant Operations & Maintenance', 'description' => 'Manufacturing floor & machinery maintenance', 'status' => 'Active']
        );

        $deptVeQa = Department::withoutGlobalScopes()->firstOrCreate(
            ['company_id' => $vishal->id, 'code' => 'VE-QA'],
            ['name' => 'Quality Assurance & Testing', 'description' => 'Component quality & metallurgic testing', 'status' => 'Active']
        );

        $deptVeHr = Department::withoutGlobalScopes()->firstOrCreate(
            ['company_id' => $vishal->id, 'code' => 'VE-HR'],
            ['name' => 'Human Resources & General Admin', 'description' => 'Personnel administration, compliance & payroll', 'status' => 'Active']
        );

        // Departments for Vrishal Eng.
        $deptVreFab = Department::withoutGlobalScopes()->firstOrCreate(
            ['company_id' => $vrishal->id, 'code' => 'VRE-FAB'],
            ['name' => 'Heavy Structural Fabrication', 'description' => 'Heavy structures, boilers & pressure vessels', 'status' => 'Active']
        );

        $deptVreEhs = Department::withoutGlobalScopes()->firstOrCreate(
            ['company_id' => $vrishal->id, 'code' => 'VRE-EHS'],
            ['name' => 'Environment, Health & Safety', 'description' => 'Workplace safety and environmental compliance', 'status' => 'Active']
        );

        // 5. Designations for Vishal Enterprise
        Designation::withoutGlobalScopes()->firstOrCreate(
            ['department_id' => $deptVeOps->id, 'code' => 'DES-PLT-SUP'],
            ['title' => 'Plant Superintendent', 'description' => 'Overall plant production supervisor', 'status' => 'Active']
        );

        Designation::withoutGlobalScopes()->firstOrCreate(
            ['department_id' => $deptVeOps->id, 'code' => 'DES-SFT-ENG'],
            ['title' => 'Shift Engineer', 'description' => 'Operational shift supervisor', 'status' => 'Active']
        );

        Designation::withoutGlobalScopes()->firstOrCreate(
            ['department_id' => $deptVeQa->id, 'code' => 'DES-SR-QA'],
            ['title' => 'Senior QA Inspector', 'description' => 'Lead quality inspector', 'status' => 'Active']
        );

        Designation::withoutGlobalScopes()->firstOrCreate(
            ['department_id' => $deptVeHr->id, 'code' => 'DES-HR-EXEC'],
            ['title' => 'Human Resource Executive', 'description' => 'Timekeeping, onboarding & payroll admin', 'status' => 'Active']
        );

        // Designations for Vrishal Eng.
        Designation::withoutGlobalScopes()->firstOrCreate(
            ['department_id' => $deptVreFab->id, 'code' => 'DES-M-WELD'],
            ['title' => 'Master Welder & Fabricator', 'description' => 'Precision arc & TIG welder', 'status' => 'Active']
        );

        Designation::withoutGlobalScopes()->firstOrCreate(
            ['department_id' => $deptVreEhs->id, 'code' => 'DES-SFT-OFF'],
            ['title' => 'Site Safety Officer', 'description' => 'Ground site safety enforcement', 'status' => 'Active']
        );

        // 6. Employee Categories for both companies
        foreach ([$vishal, $vrishal] as $comp) {
            Category::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $comp->id, 'code' => $comp->code . '-CAT-STF'],
                ['name' => 'Permanent Staff', 'notice_period_days' => 30, 'description' => 'Full-time salaried staff', 'status' => 'Active']
            );

            Category::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $comp->id, 'code' => $comp->code . '-CAT-WRK'],
                ['name' => 'Shop Floor Worker', 'notice_period_days' => 15, 'description' => 'Plant wage workers', 'status' => 'Active']
            );

            Category::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $comp->id, 'code' => $comp->code . '-CAT-GND'],
                ['name' => 'Ground Staff', 'notice_period_days' => 30, 'description' => 'Site-based project workers', 'status' => 'Active']
            );

            Category::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $comp->id, 'code' => $comp->code . '-CAT-EXE'],
                ['name' => 'Senior Executive', 'notice_period_days' => 60, 'description' => 'Management cadre', 'status' => 'Active']
            );
        }

        // 7. Contractors
        Contractor::withoutGlobalScopes()->firstOrCreate(
            ['company_id' => $vishal->id, 'code' => 'CON-APEX'],
            [
                'agency_name' => 'Apex Industrial Manpower Agency',
                'contact_person' => 'Ramesh Patil',
                'phone' => '+91 98230 11223',
                'email' => 'ramesh@apexmmpower.com',
                'pan' => 'AACCA1234K',
                'gstin' => '27AACCA1234K1Z0',
                'address' => 'Shop 14, MIDC Commercial Complex, Bhosari, Pune',
                'status' => 'Active',
            ]
        );

        Contractor::withoutGlobalScopes()->firstOrCreate(
            ['company_id' => $vrishal->id, 'code' => 'CON-PRE'],
            [
                'agency_name' => 'Precision Technical Contractors',
                'contact_person' => 'Sunil Shinde',
                'phone' => '+91 98224 55667',
                'email' => 'sunil@precisioncontractors.in',
                'pan' => 'BBCDB5678L',
                'gstin' => '27BBCDB5678L1Z3',
                'address' => 'Plot 88, Chakan Industrial Phase II, Pune',
                'status' => 'Active',
            ]
        );

        // 8. Users for all 4 Roles
        // HO Team Admin (Cross-company switch enabled)
        User::firstOrCreate(
            ['email' => 'ho_admin@enterprise.com'],
            [
                'name' => 'Vikram Singhania (HO Admin)',
                'password' => Hash::make('password'),
                'company_id' => $vishal->id,
                'role_id' => $hoAdminRole->id,
                'employee_code' => 'HO-ADMIN-01',
                'phone' => '+91 98000 00001',
                'is_active' => true,
                'can_switch_company' => true, // Allowed to switch across both companies!
            ]
        );

        // HR Executive for Vishal Enterprise
        User::firstOrCreate(
            ['email' => 'hr@vishal.com'],
            [
                'name' => 'Pooja Kulkarni (HR Exec)',
                'password' => Hash::make('password'),
                'company_id' => $vishal->id,
                'role_id' => $hrRole->id,
                'employee_code' => 'VE-HR-001',
                'phone' => '+91 98000 00002',
                'is_active' => true,
                'can_switch_company' => false,
            ]
        );

        // HR Executive for Vrishal Eng.
        User::firstOrCreate(
            ['email' => 'hr@vrishal.com'],
            [
                'name' => 'Anil Deshmukh (HR Exec)',
                'password' => Hash::make('password'),
                'company_id' => $vrishal->id,
                'role_id' => $hrRole->id,
                'employee_code' => 'VRE-HR-001',
                'phone' => '+91 98000 00003',
                'is_active' => true,
                'can_switch_company' => false,
            ]
        );

        // Manager MSS for Vishal Enterprise
        User::firstOrCreate(
            ['email' => 'manager@vishal.com'],
            [
                'name' => 'Rajesh Sharma (Shift Supervisor)',
                'password' => Hash::make('password'),
                'company_id' => $vishal->id,
                'role_id' => $managerRole->id,
                'employee_code' => 'VE-MGR-010',
                'phone' => '+91 98000 00004',
                'is_active' => true,
                'can_switch_company' => false,
            ]
        );

        // Employee ESS for Vishal Enterprise
        User::firstOrCreate(
            ['email' => 'employee@vishal.com'],
            [
                'name' => 'Sachin Jadhav (Technician)',
                'password' => Hash::make('password'),
                'company_id' => $vishal->id,
                'role_id' => $employeeRole->id,
                'employee_code' => 'VE-EMP-1042',
                'phone' => '+91 98000 00005',
                'is_active' => true,
                'can_switch_company' => false,
            ]
        );

        $this->call([
            Phase2Seeder::class,
            Phase3Seeder::class,
            Phase4Seeder::class,
            Phase5Seeder::class,
            NotificationDefaultsSeeder::class,
            Phase7DocumentTemplateSeeder::class,
        ]);
    }
}
