<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Company;
use App\Models\Contractor;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeAddress;
use App\Models\EmployeeBankDetail;
use App\Models\EmployeeStatutoryDetail;
use App\Models\EmployeeVisaDetail;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkEmployeeImportService
{
    public const TEMPLATE_COLUMNS = [
        'first_name' => 'First Name *',
        'middle_name' => 'Middle Name',
        'last_name' => 'Last Name *',
        'gender' => 'Gender * (Male/Female/Other)',
        'date_of_birth' => 'Date of Birth * (YYYY-MM-DD)',
        'phone' => 'Phone * (10 Digits)',
        'email' => 'Email Address',
        'joining_date' => 'Date of Joining * (YYYY-MM-DD)',
        'location' => 'Branch / Location *',
        'department' => 'Department *',
        'designation' => 'Designation *',
        'category' => 'Cadre Category (Staff/Worker/Executive)',
        'employment_type' => 'Employment Type (Permanent/Contract/Intern)',
        'onboarding_channel' => 'Channel (Direct/Contractor/Referral)',
        'contractor' => 'Contractor Vendor Name',
        'grade' => 'Grade / Band (E1/E2/M1/W1)',
        'custom_employee_code' => 'Custom Employee Code (Leave blank for auto)',
        'gate_pass_number' => 'Gate Pass #',
        'aadhaar_number' => 'Aadhaar (12 Digits)',
        'pan_number' => 'PAN (10 Chars)',
        'marital_status' => 'Marital Status (Single/Married)',
        'blood_group' => 'Blood Group (A+/B+/O+/AB+)',
    ];

    public function __construct(
        protected CompanyContextService $companyContext,
        protected EmployeeCodeGeneratorService $codeGenerator,
        protected BlacklistMatchingService $blacklistService,
        protected LicenseEntitlementService $licenseService,
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Generate template for download in CSV or XLSX format.
     */
    public function downloadTemplate(string $format, int $companyId): StreamedResponse
    {
        $company = Company::find($companyId);
        $locations = Location::where('company_id', $companyId)->where('status', 'Active')->pluck('name')->toArray();
        $departments = Department::where('company_id', $companyId)->where('status', 'Active')->pluck('name')->toArray();
        $designations = Designation::where('status', 'Active')->pluck('title')->take(10)->toArray();
        $categories = Category::where('company_id', $companyId)->where('status', 'Active')->pluck('name')->toArray();

        $sampleLoc = !empty($locations) ? $locations[0] : 'Pune Plant';
        $sampleDept = !empty($departments) ? $departments[0] : 'Production & Assembly';
        $sampleDesig = !empty($designations) ? $designations[0] : 'Production Engineer';
        $sampleCat = !empty($categories) ? $categories[0] : 'Permanent Staff';

        $sampleRows = [
            [
                'first_name' => 'Amit',
                'middle_name' => 'Kumar',
                'last_name' => 'Sharma',
                'gender' => 'Male',
                'date_of_birth' => '1995-05-12',
                'phone' => '9823011223',
                'email' => 'amit.sharma@example.com',
                'joining_date' => date('Y-m-d'),
                'location' => $sampleLoc,
                'department' => $sampleDept,
                'designation' => $sampleDesig,
                'category' => $sampleCat,
                'employment_type' => 'Permanent',
                'onboarding_channel' => 'Direct',
                'contractor' => '',
                'grade' => 'E1',
                'custom_employee_code' => '',
                'gate_pass_number' => 'GP-' . rand(1000, 9999),
                'aadhaar_number' => '234567890123',
                'pan_number' => 'ABCDE1234F',
                'marital_status' => 'Single',
                'blood_group' => 'B+',
            ],
            [
                'first_name' => 'Pooja',
                'middle_name' => '',
                'last_name' => 'Verma',
                'gender' => 'Female',
                'date_of_birth' => '1998-09-20',
                'phone' => '9823044556',
                'email' => 'pooja.verma@example.com',
                'joining_date' => date('Y-m-d'),
                'location' => $sampleLoc,
                'department' => $sampleDept,
                'designation' => $sampleDesig,
                'category' => $sampleCat,
                'employment_type' => 'Permanent',
                'onboarding_channel' => 'Direct',
                'contractor' => '',
                'grade' => 'E2',
                'custom_employee_code' => '',
                'gate_pass_number' => 'GP-' . rand(1000, 9999),
                'aadhaar_number' => '876543210987',
                'pan_number' => 'PQRST5678K',
                'marital_status' => 'Married',
                'blood_group' => 'O+',
            ],
        ];

        if ($format === 'xlsx') {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Employee Import Template');

            // Headers
            $headers = array_values(self::TEMPLATE_COLUMNS);
            $sheet->fromArray($headers, null, 'A1');

            // Header Style
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0D9488']
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ];
            $sheet->getStyle('A1:V1')->applyFromArray($headerStyle);
            $sheet->getRowDimension(1)->setRowHeight(28);

            // Sample rows
            $sheet->fromArray(array_values($sampleRows[0]), null, 'A2');
            $sheet->fromArray(array_values($sampleRows[1]), null, 'A3');

            foreach (range('A', 'V') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Reference Sheet
            $refSheet = $spreadsheet->createSheet();
            $refSheet->setTitle('Reference Master Values');
            $refSheet->setCellValue('A1', 'Company Reference Data (Use exact names in template)');
            $refSheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);

            $refSheet->setCellValue('A3', 'Valid Branches / Locations');
            $refSheet->setCellValue('B3', 'Valid Departments');
            $refSheet->setCellValue('C3', 'Valid Cadre Categories');
            $refSheet->setCellValue('D3', 'Common Designations');
            $refSheet->getStyle('A3:D3')->getFont()->setBold(true);

            $maxRows = max(count($locations), count($departments), count($categories), count($designations));
            for ($i = 0; $i < $maxRows; $i++) {
                $row = $i + 4;
                if (isset($locations[$i])) $refSheet->setCellValue("A{$row}", $locations[$i]);
                if (isset($departments[$i])) $refSheet->setCellValue("B{$row}", $departments[$i]);
                if (isset($categories[$i])) $refSheet->setCellValue("C{$row}", $categories[$i]);
                if (isset($designations[$i])) $refSheet->setCellValue("D{$row}", $designations[$i]);
            }
            foreach (['A', 'B', 'C', 'D'] as $col) {
                $refSheet->getColumnDimension($col)->setAutoSize(true);
            }

            $spreadsheet->setActiveSheetIndex(0);

            $fileName = 'employee_bulk_import_template_' . Str::slug($company?->code ?? 'VRE') . '.xlsx';
            return response()->streamDownload(function () use ($spreadsheet) {
                $writer = new XlsxWriter($spreadsheet);
                $writer->save('php://output');
            }, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        // CSV Template
        $fileName = 'employee_bulk_import_template_' . Str::slug($company?->code ?? 'VRE') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        return response()->stream(function () use ($sampleRows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, array_values(self::TEMPLATE_COLUMNS));
            foreach ($sampleRows as $row) {
                fputcsv($handle, array_values($row));
            }
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Parse uploaded file and validate all records.
     */
    public function parseAndValidate(UploadedFile $file, int $companyId, int $tenantId): array
    {
        $rows = $this->readRowsFromFile($file);
        if (empty($rows)) {
            return [
                'success' => false,
                'message' => 'The uploaded file is empty or contains no data rows.',
                'total_rows' => 0,
                'valid_count' => 0,
                'invalid_count' => 0,
                'duplicate_count' => 0,
                'blacklisted_count' => 0,
                'preview_records' => [],
                'errors' => [],
                'can_proceed' => false,
            ];
        }

        // Preload company lookup tables
        $locations = Location::where('company_id', $companyId)->get()->keyBy(fn($l) => strtolower(trim($l->name)));
        $departments = Department::where('company_id', $companyId)->get()->keyBy(fn($d) => strtolower(trim($d->name)));
        $designations = Designation::all()->keyBy(fn($d) => strtolower(trim($d->title ?? $d->name)));
        $categories = Category::where('company_id', $companyId)->get()->keyBy(fn($c) => strtolower(trim($c->name)));
        $contractors = Contractor::where('company_id', $companyId)->get()->keyBy(fn($c) => strtolower(trim($c->name)));

        // Preload existing unique identifiers to check database duplicates
        $existingPhones = Employee::where('company_id', $companyId)->whereNotNull('phone')->pluck('phone')->map(fn($p) => trim($p))->flip();
        $existingEmails = Employee::where('company_id', $companyId)->whereNotNull('email')->pluck('email')->map(fn($e) => strtolower(trim($e)))->flip();
        $existingCodes = Employee::where('company_id', $companyId)->whereNotNull('employee_code')->pluck('employee_code')->map(fn($c) => strtoupper(trim($c)))->flip();

        $seenPhonesInBatch = [];
        $seenEmailsInBatch = [];
        $seenCodesInBatch = [];

        $validRows = [];
        $invalidRows = [];
        $duplicateRows = [];
        $blacklistedRows = [];
        $allErrors = [];
        $previewRecords = [];

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // Row 1 is header
            $rowErrors = [];

            // 1. Mandatory Names
            $firstName = trim($row['first_name'] ?? '');
            $lastName = trim($row['last_name'] ?? '');
            $middleName = trim($row['middle_name'] ?? '');

            if (empty($firstName)) {
                $rowErrors[] = 'First name is required.';
            }
            if (empty($lastName)) {
                $rowErrors[] = 'Last name is required.';
            }

            // 2. Gender
            $gender = ucfirst(strtolower(trim($row['gender'] ?? '')));
            if (!in_array($gender, ['Male', 'Female', 'Other'])) {
                $rowErrors[] = 'Gender must be Male, Female, or Other.';
            }

            // 3. Date of Birth & Age >= 18
            $dobRaw = trim($row['date_of_birth'] ?? '');
            $dob = $this->parseDate($dobRaw);
            if (!$dob) {
                $rowErrors[] = 'Invalid Date of Birth. Required format: YYYY-MM-DD.';
            } elseif ($dob->diffInYears(Carbon::today()) < 18) {
                $rowErrors[] = 'Employee must be at least 18 years of age (DOB: ' . $dob->format('Y-m-d') . ').';
            }

            // 4. Phone Number
            $phone = preg_replace('/[^0-9]/', '', trim($row['phone'] ?? ''));
            if (empty($phone) || strlen($phone) < 10 || strlen($phone) > 13) {
                $rowErrors[] = 'Valid 10-digit phone number is required.';
            } elseif (isset($existingPhones[$phone])) {
                $duplicateRows[] = $rowNum;
                $rowErrors[] = "Duplicate phone ({$phone}): Already registered to an employee in this company.";
            } elseif (isset($seenPhonesInBatch[$phone])) {
                $duplicateRows[] = $rowNum;
                $rowErrors[] = "Duplicate phone ({$phone}): Appears multiple times in this uploaded batch (Row {$seenPhonesInBatch[$phone]}).";
            } else {
                $seenPhonesInBatch[$phone] = $rowNum;
            }

            // 5. Email (Optional, but if given must be valid & unique)
            $email = strtolower(trim($row['email'] ?? ''));
            if (!empty($email)) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $rowErrors[] = "Invalid email format: {$email}.";
                } elseif (isset($existingEmails[$email])) {
                    $duplicateRows[] = $rowNum;
                    $rowErrors[] = "Duplicate email ({$email}): Already registered in this company.";
                } elseif (isset($seenEmailsInBatch[$email])) {
                    $duplicateRows[] = $rowNum;
                    $rowErrors[] = "Duplicate email ({$email}): Appears multiple times in this batch (Row {$seenEmailsInBatch[$email]}).";
                } else {
                    $seenEmailsInBatch[$email] = $rowNum;
                }
            }

            // 6. Joining Date
            $dojRaw = trim($row['joining_date'] ?? '');
            $doj = $this->parseDate($dojRaw) ?? Carbon::today();

            // 7. Location / Branch Lookup
            $locName = strtolower(trim($row['location'] ?? ''));
            $matchedLocation = $locations->get($locName);
            if (!$matchedLocation) {
                // Try partial match
                $matchedLocation = $locations->first(fn($l) => str_contains(strtolower($l->name), $locName));
            }
            if (!$matchedLocation) {
                $rowErrors[] = "Branch/Location '{$row['location']}' was not found in company master.";
            }

            // 8. Department Lookup
            $deptName = strtolower(trim($row['department'] ?? ''));
            $matchedDept = $departments->get($deptName);
            if (!$matchedDept) {
                $matchedDept = $departments->first(fn($d) => str_contains(strtolower($d->name), $deptName));
            }
            if (!$matchedDept) {
                $rowErrors[] = "Department '{$row['department']}' was not found in company master.";
            }

            // 9. Designation Lookup
            $desigName = strtolower(trim($row['designation'] ?? ''));
            $matchedDesig = $designations->get($desigName);
            if (!$matchedDesig) {
                $matchedDesig = $designations->first(fn($d) => str_contains(strtolower($d->title ?? $d->name), $desigName));
            }
            if (!$matchedDesig) {
                $rowErrors[] = "Designation '{$row['designation']}' was not found in master list.";
            }

            // 10. Cadre Category
            $catName = strtolower(trim($row['category'] ?? ''));
            $matchedCat = $categories->get($catName) ?? $categories->first();

            // 11. Channel & Contractor
            $channel = ucfirst(strtolower(trim($row['onboarding_channel'] ?? 'Direct')));
            if (!in_array($channel, ['Direct', 'Contractor', 'Referral', 'Agency'])) {
                $channel = 'Direct';
            }
            $contractorId = null;
            if ($channel === 'Contractor') {
                $conName = strtolower(trim($row['contractor'] ?? ''));
                $matchedCon = $contractors->get($conName) ?? $contractors->first();
                if (!$matchedCon) {
                    $rowErrors[] = "Contractor vendor name '{$row['contractor']}' not found.";
                } else {
                    $contractorId = $matchedCon->id;
                }
            }

            // 12. Custom Employee Code (if specified)
            $customCode = strtoupper(trim($row['custom_employee_code'] ?? ''));
            if (!empty($customCode)) {
                if (isset($existingCodes[$customCode])) {
                    $duplicateRows[] = $rowNum;
                    $rowErrors[] = "Custom employee code '{$customCode}' is already assigned.";
                } elseif (isset($seenCodesInBatch[$customCode])) {
                    $duplicateRows[] = $rowNum;
                    $rowErrors[] = "Custom code '{$customCode}' duplicated in this batch (Row {$seenCodesInBatch[$customCode]}).";
                } else {
                    $seenCodesInBatch[$customCode] = $rowNum;
                }
            }

            // 13. PAN Number regex check
            $pan = strtoupper(trim($row['pan_number'] ?? ''));
            if (!empty($pan) && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan)) {
                $rowErrors[] = "Invalid PAN format: {$pan}. Must follow ABCDE1234F.";
            }

            // 14. Aadhaar check
            $aadhaar = preg_replace('/[^0-9]/', '', trim($row['aadhaar_number'] ?? ''));
            if (!empty($aadhaar) && strlen($aadhaar) !== 12) {
                $rowErrors[] = "Invalid Aadhaar number: must be exactly 12 digits.";
            }

            // 15. Central Blacklist Check
            $blacklistCheck = $this->blacklistService->checkIdentity([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'date_of_birth' => $dob?->format('Y-m-d'),
                'phone' => $phone,
                'pan_number' => $pan,
                'aadhaar_number' => $aadhaar,
            ]);
            if ($blacklistCheck['is_blocked']) {
                $blacklistedRows[] = $rowNum;
                $rowErrors[] = "SECURITY HALT: Candidate matches an active entry on the Central Blacklist.";
            }

            // Record status
            $fullName = trim("{$firstName} {$middleName} {$lastName}");
            if (!empty($rowErrors)) {
                $invalidRows[] = $rowNum;
                foreach ($rowErrors as $err) {
                    $allErrors[] = [
                        'row' => $rowNum,
                        'name' => $fullName ?: 'Unknown',
                        'message' => $err,
                    ];
                }
            } else {
                $validRows[] = [
                    'row' => $rowNum,
                    'first_name' => $firstName,
                    'middle_name' => $middleName ?: null,
                    'last_name' => $lastName,
                    'full_name' => $fullName,
                    'gender' => $gender,
                    'date_of_birth' => $dob->format('Y-m-d'),
                    'phone' => $phone,
                    'email' => $email ?: null,
                    'joining_date' => $doj->format('Y-m-d'),
                    'location_id' => $matchedLocation->id,
                    'location_name' => $matchedLocation->name,
                    'department_id' => $matchedDept->id,
                    'department_name' => $matchedDept->name,
                    'designation_id' => $matchedDesig->id,
                    'designation_title' => $matchedDesig->title ?? $matchedDesig->name,
                    'category_id' => $matchedCat?->id,
                    'employment_type' => trim($row['employment_type'] ?? 'Permanent') ?: 'Permanent',
                    'onboarding_channel' => $channel,
                    'contractor_id' => $contractorId,
                    'grade' => trim($row['grade'] ?? 'E1') ?: 'E1',
                    'custom_employee_code' => $customCode ?: null,
                    'gate_pass_number' => trim($row['gate_pass_number'] ?? '') ?: null,
                    'pan_number' => $pan ?: null,
                    'aadhaar_number' => $aadhaar ?: null,
                    'marital_status' => trim($row['marital_status'] ?? '') ?: null,
                    'blood_group' => trim($row['blood_group'] ?? '') ?: null,
                ];
            }

            // Preview record for UI table
            if (count($previewRecords) < 15) {
                $previewRecords[] = [
                    'row' => $rowNum,
                    'name' => $fullName ?: 'Row ' . $rowNum,
                    'gender' => $gender,
                    'dob' => $dobRaw,
                    'phone' => $phone,
                    'location' => $matchedLocation?->name ?? ($row['location'] ?? '—'),
                    'department' => $matchedDept?->name ?? ($row['department'] ?? '—'),
                    'designation' => $matchedDesig?->title ?? ($row['designation'] ?? '—'),
                    'has_errors' => !empty($rowErrors),
                    'error_count' => count($rowErrors),
                ];
            }
        }

        // Cache the validated batch with a unique token for confirmation
        $batchToken = 'emp_batch_' . Str::random(32);
        Cache::put("import_batch_{$batchToken}", [
            'company_id' => $companyId,
            'tenant_id' => $tenantId,
            'valid_rows' => $validRows,
            'errors' => $allErrors,
            'total_rows' => count($rows),
        ], now()->addHours(2));

        return [
            'success' => true,
            'batch_token' => $batchToken,
            'total_rows' => count($rows),
            'valid_count' => count($validRows),
            'invalid_count' => count($invalidRows),
            'duplicate_count' => count(array_unique($duplicateRows)),
            'blacklisted_count' => count($blacklistedRows),
            'preview_records' => $previewRecords,
            'errors' => array_slice($allErrors, 0, 50),
            'can_proceed' => count($validRows) > 0,
        ];
    }

    /**
     * Commit validated batch to database.
     */
    public function commitBatch(string $batchToken, int $companyId, int $tenantId, $actor): array
    {
        $cached = Cache::get("import_batch_{$batchToken}");
        if (!$cached || empty($cached['valid_rows'])) {
            return [
                'success' => false,
                'message' => 'Batch session expired or no valid rows found to commit. Please re-upload.',
                'imported_count' => 0,
                'failed_count' => 0,
            ];
        }

        if ($cached['company_id'] !== $companyId) {
            return [
                'success' => false,
                'message' => 'Company context mismatch. Import aborted for security.',
                'imported_count' => 0,
                'failed_count' => 0,
            ];
        }

        $validRows = $cached['valid_rows'];

        // Check subscription quota
        $quota = $this->licenseService->canAddEmployee(companyId: $companyId);
        if (!$quota['allowed']) {
            return [
                'success' => false,
                'message' => 'Subscription quota exceeded: ' . $quota['message'],
                'imported_count' => 0,
                'failed_count' => count($validRows),
            ];
        }

        $imported = [];
        $failed = [];

        DB::transaction(function () use ($validRows, $companyId, $tenantId, $actor, &$imported, &$failed) {
            foreach ($validRows as $row) {
                try {
                    // Generate sequential employee code if custom code not supplied
                    $empCode = $row['custom_employee_code'];
                    $generationMethod = 'manual';

                    if (empty($empCode)) {
                        $empCode = $this->codeGenerator->generateCode($companyId, [
                            'location_id' => $row['location_id'],
                            'department_id' => $row['department_id'],
                        ]);
                        $generationMethod = 'auto';
                    }

                    $employee = Employee::create([
                        'tenant_id' => $tenantId,
                        'company_id' => $companyId,
                        'employee_code' => $empCode,
                        'first_name' => $row['first_name'],
                        'middle_name' => $row['middle_name'],
                        'last_name' => $row['last_name'],
                        'gender' => $row['gender'],
                        'date_of_birth' => $row['date_of_birth'],
                        'phone' => $row['phone'],
                        'email' => $row['email'],
                        'date_of_joining' => $row['joining_date'],
                        'location_id' => $row['location_id'],
                        'department_id' => $row['department_id'],
                        'designation_id' => $row['designation_id'],
                        'category_id' => $row['category_id'],
                        'contractor_id' => $row['contractor_id'],
                        'employee_type' => $row['employment_type'],
                        'onboarding_channel' => $row['onboarding_channel'],
                        'grade' => $row['grade'],
                        'gate_pass_number' => $row['gate_pass_number'],
                        'pan_number' => $row['pan_number'],
                        'marital_status' => $row['marital_status'],
                        'blood_group' => $row['blood_group'],
                        'status' => 'Active',
                        'confirmation_status' => 'In Probation',
                        'code_generation_method' => $generationMethod,
                        'created_by' => $actor?->id,
                    ]);

                    // Create basic supporting records
                    EmployeeAddress::create([
                        'employee_id' => $employee->id,
                        'current_country' => 'India',
                        'permanent_country' => 'India',
                    ]);

                    EmployeeStatutoryDetail::create([
                        'employee_id' => $employee->id,
                        'has_pf' => true,
                        'has_esic' => true,
                        'has_pt' => true,
                    ]);

                    EmployeeBankDetail::create([
                        'employee_id' => $employee->id,
                        'bank_account_type' => 'Salary',
                        'payment_mode' => 'Bank Transfer',
                    ]);

                    EmployeeVisaDetail::create([
                        'employee_id' => $employee->id,
                        'visa_required' => false,
                        'country_of_employment' => 'India',
                    ]);

                    // Audit Log
                    $this->auditLogService->log(
                        action: 'Bulk Employee Import Committed',
                        entityType: 'Employee',
                        entityId: $employee->id,
                        oldValues: [],
                        newValues: [
                            'employee_code' => $employee->employee_code,
                            'name' => $employee->full_name,
                            'source' => 'Bulk Import Engine',
                        ],
                        companyId: $companyId,
                        actor: $actor
                    );

                    $imported[] = $employee->employee_code;
                } catch (\Throwable $e) {
                    $failed[] = [
                        'row' => $row['row'],
                        'name' => $row['full_name'],
                        'message' => $e->getMessage(),
                    ];
                }
            }
        });

        Cache::forget("import_batch_{$batchToken}");

        return [
            'success' => true,
            'imported_count' => count($imported),
            'failed_count' => count($failed),
            'imported_codes' => array_slice($imported, 0, 10),
            'failed_details' => $failed,
            'message' => count($imported) . ' employees imported successfully into Workforce Directory.',
        ];
    }

    /**
     * Read tabular rows from uploaded CSV or Excel file.
     */
    protected function readRowsFromFile(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $filePath = $file->getRealPath();

        $rows = [];

        if ($ext === 'csv' || $ext === 'txt') {
            $handle = fopen($filePath, 'r');
            if (!$handle) return [];

            // Detect and remove UTF-8 BOM
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            $rawHeader = fgetcsv($handle);
            if (!$rawHeader) {
                fclose($handle);
                return [];
            }

            $headerMap = $this->normalizeHeaderMap($rawHeader);

            while (($data = fgetcsv($handle)) !== false) {
                // Skip empty lines
                if (empty(array_filter($data, fn($v) => trim($v) !== ''))) {
                    continue;
                }
                $row = [];
                foreach ($headerMap as $key => $colIdx) {
                    $row[$key] = isset($data[$colIdx]) ? trim($data[$colIdx]) : '';
                }
                $rows[] = $row;
            }
            fclose($handle);
        } else {
            // Read Excel via PhpSpreadsheet
            try {
                $reader = new XlsxReader();
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($filePath);
                $sheet = $spreadsheet->getActiveSheet();
                $sheetData = $sheet->toArray(null, true, true, false);

                if (empty($sheetData)) return [];

                $rawHeader = array_shift($sheetData);
                $headerMap = $this->normalizeHeaderMap($rawHeader);

                foreach ($sheetData as $data) {
                    if (empty(array_filter($data, fn($v) => trim((string)$v) !== ''))) {
                        continue;
                    }
                    $row = [];
                    foreach ($headerMap as $key => $colIdx) {
                        $row[$key] = isset($data[$colIdx]) ? trim((string)$data[$colIdx]) : '';
                    }
                    $rows[] = $row;
                }
            } catch (\Throwable $e) {
                return [];
            }
        }

        return $rows;
    }

    /**
     * Map fuzzy header column names to standard keys.
     */
    protected function normalizeHeaderMap(array $rawHeader): array
    {
        $aliases = [
            'first_name' => ['first name', 'firstname', 'first_name', 'fname'],
            'middle_name' => ['middle name', 'middlename', 'middle_name', 'mname'],
            'last_name' => ['last name', 'lastname', 'last_name', 'lname', 'surname'],
            'gender' => ['gender', 'sex'],
            'date_of_birth' => ['date of birth', 'dob', 'date_of_birth', 'birth date'],
            'phone' => ['phone', 'mobile', 'phone number', 'contact', 'mobile number'],
            'email' => ['email', 'email address', 'official email', 'personal email'],
            'joining_date' => ['date of joining', 'joining date', 'doj', 'joining_date'],
            'location' => ['branch', 'location', 'site', 'branch / location', 'site / location'],
            'department' => ['department', 'dept'],
            'designation' => ['designation', 'role', 'title', 'job title'],
            'category' => ['category', 'cadre', 'cadre category', 'employee category'],
            'employment_type' => ['employment type', 'emp type', 'type'],
            'onboarding_channel' => ['channel', 'onboarding channel', 'recruitment channel'],
            'contractor' => ['contractor', 'contractor vendor', 'vendor'],
            'grade' => ['grade', 'band', 'grade / band'],
            'custom_employee_code' => ['custom employee code', 'employee code', 'code', 'emp code'],
            'gate_pass_number' => ['gate pass', 'gate pass #', 'gate pass number'],
            'aadhaar_number' => ['aadhaar', 'aadhaar number', 'aadhaar card'],
            'pan_number' => ['pan', 'pan number', 'pan card'],
            'marital_status' => ['marital status', 'marital_status'],
            'blood_group' => ['blood group', 'blood_group', 'blood'],
        ];

        $map = [];
        foreach ($rawHeader as $idx => $headerText) {
            $clean = strtolower(trim(preg_replace('/[^a-zA-Z0-9_\s]/', '', (string)$headerText)));
            foreach ($aliases as $key => $alList) {
                if (in_array($clean, $alList)) {
                    $map[$key] = $idx;
                    break;
                }
            }
        }

        return $map;
    }

    /**
     * Safely parse various date formats.
     */
    protected function parseDate(?string $value): ?Carbon
    {
        if (empty($value)) return null;

        $value = trim($value);

        // Handle Excel numeric serial dates
        if (is_numeric($value) && (int)$value > 20000 && (int)$value < 60000) {
            try {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value));
            } catch (\Throwable) {}
        }

        $formats = ['Y-m-d', 'd-m-Y', 'd/m/Y', 'm/d/Y', 'Y/m/d', 'd.m.Y'];
        foreach ($formats as $fmt) {
            try {
                $d = Carbon::createFromFormat($fmt, $value);
                if ($d && $d->year > 1900 && $d->year < 2100) {
                    return $d;
                }
            } catch (\Throwable) {}
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
