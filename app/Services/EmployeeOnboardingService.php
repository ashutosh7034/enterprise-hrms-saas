<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeGatePassHistory;
use App\Models\EmployeePreviousEmployment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EmployeeOnboardingService
{
    /**
     * Generate next sequential Employee ID based on company code and active configuration.
     */
    public function generateEmployeeCode(int $companyId, string $companyCode, ?int $locationId = null, ?int $departmentId = null): string
    {
        return app(\App\Services\EmployeeCodeGeneratorService::class)->generateNextCode($companyId, $locationId, $departmentId);
    }

    /**
     * Check for duplicate candidate across both companies.
     * Returns array of detected matches.
     */
    public function checkDuplicates(
        ?string $aadhaarNumber,
        ?string $panNumber,
        string $phone,
        string $firstName,
        string $lastName,
        string $dob,
        ?int $excludeEmployeeId = null
    ): array {
        $matches = [];
        $query = Employee::withoutGlobalScopes()->with('company');

        if ($excludeEmployeeId) {
            $query->where('id', '!=', $excludeEmployeeId);
        }

        $allEmployees = $query->get();

        $targetAadhaarHash = $aadhaarNumber ? hash('sha256', preg_replace('/\D/', '', $aadhaarNumber)) : null;
        $cleanPan = $panNumber ? strtoupper(trim($panNumber)) : null;
        $cleanPhone = preg_replace('/\D/', '', $phone);
        $cleanName = strtolower(trim("{$firstName} {$lastName}"));

        foreach ($allEmployees as $emp) {
            // 1. Aadhaar Match
            if ($targetAadhaarHash && $emp->aadhaar_hash === $targetAadhaarHash) {
                $matches[] = [
                    'field' => 'Aadhaar',
                    'match_type' => 'Exact Token Match',
                    'company' => $emp->company?->name,
                    'employee_code' => $emp->employee_code,
                    'status' => $emp->status,
                ];
            }

            // 2. PAN Match
            if ($cleanPan && strtoupper(trim($emp->pan_number)) === $cleanPan) {
                $matches[] = [
                    'field' => 'PAN',
                    'match_type' => 'Exact PAN Match',
                    'company' => $emp->company?->name,
                    'employee_code' => $emp->employee_code,
                    'status' => $emp->status,
                ];
            }

            // 3. Mobile Number Match
            if ($cleanPhone && preg_replace('/\D/', '', $emp->phone) === $cleanPhone) {
                $matches[] = [
                    'field' => 'Mobile Phone',
                    'match_type' => 'Exact Phone Match',
                    'company' => $emp->company?->name,
                    'employee_code' => $emp->employee_code,
                    'status' => $emp->status,
                ];
            }

            // 4. Name & DOB Match
            $empName = strtolower(trim("{$emp->first_name} {$emp->last_name}"));
            if ($empName === $cleanName && $emp->date_of_birth?->format('Y-m-d') === $dob) {
                $matches[] = [
                    'field' => 'Name & DOB',
                    'match_type' => 'Exact Name & Birthdate Match',
                    'company' => $emp->company?->name,
                    'employee_code' => $emp->employee_code,
                    'status' => $emp->status,
                ];
            }
        }

        return $matches;
    }

    /**
     * Check previous employment history across both companies.
     */
    public function findPreviousEmployments(
        ?string $aadhaarHash,
        ?string $panNumber,
        string $phone,
        string $firstName,
        string $lastName,
        string $dob
    ): array {
        $matches = [];
        $query = Employee::withoutGlobalScopes()->with(['company', 'department', 'designation', 'previousEmployments']);

        $candidates = $query->get();
        $cleanPan = $panNumber ? strtoupper(trim($panNumber)) : null;
        $cleanPhone = preg_replace('/\D/', '', $phone);
        $cleanName = strtolower(trim("{$firstName} {$lastName}"));

        foreach ($candidates as $cand) {
            $isMatch = false;
            if ($aadhaarHash && $cand->aadhaar_hash === $aadhaarHash) {
                $isMatch = true;
            } elseif ($cleanPan && strtoupper(trim($cand->pan_number)) === $cleanPan) {
                $isMatch = true;
            } elseif ($cleanPhone && preg_replace('/\D/', '', $cand->phone) === $cleanPhone) {
                $isMatch = true;
            } elseif (strtolower(trim("{$cand->first_name} {$cand->last_name}")) === $cleanName && $cand->date_of_birth?->format('Y-m-d') === $dob) {
                $isMatch = true;
            }

            if ($isMatch) {
                $matches[] = [
                    'previous_employee_id' => $cand->id,
                    'previous_employee_code' => $cand->employee_code,
                    'company_name' => $cand->company?->name,
                    'department' => $cand->department?->name,
                    'designation' => $cand->designation?->title,
                    'joining_date' => $cand->date_of_joining?->format('Y-m-d'),
                    'status' => $cand->status,
                    'gate_pass' => $cand->gate_pass_number,
                ];
            }
        }

        return $matches;
    }

    /**
     * Update employee Gate Pass Number with uniqueness validation and audit logging.
     */
    public function updateGatePass(Employee $employee, ?string $newGatePass, ?string $reason = null): void
    {
        $newGatePass = $newGatePass ? trim($newGatePass) : null;
        $oldGatePass = $employee->gate_pass_number;

        if ($oldGatePass === $newGatePass) {
            return;
        }

        if ($newGatePass) {
            // Check uniqueness in company scope (or system-wide toggle)
            $exists = Employee::withoutGlobalScopes()
                ->where('company_id', $employee->company_id)
                ->where('gate_pass_number', $newGatePass)
                ->where('id', '!=', $employee->id)
                ->exists();

            if ($exists) {
                throw new InvalidArgumentException("Gate Pass Number '{$newGatePass}' is already assigned to another employee in this company.");
            }
        }

        DB::transaction(function () use ($employee, $oldGatePass, $newGatePass, $reason) {
            $employee->update(['gate_pass_number' => $newGatePass]);

            EmployeeGatePassHistory::create([
                'employee_id' => $employee->id,
                'previous_gate_pass' => $oldGatePass,
                'new_gate_pass' => $newGatePass,
                'reason' => $reason ?? 'Gate pass update',
                'changed_by' => Auth::id(),
            ]);
        });
    }

    /**
     * Check if a duplicate employee exists in the company.
     */
    public function checkDuplicate(int $companyId, array $data): ?Employee
    {
        $phone = preg_replace('/\D/', '', $data['phone'] ?? '');
        $pan = !empty($data['pan_number']) ? strtoupper(trim($data['pan_number'])) : null;
        $aadhaar = !empty($data['aadhaar_number']) ? $this->hashAadhaar($data['aadhaar_number']) : null;

        if (!$pan && !$aadhaar && (!$phone || strlen($phone) < 10)) {
            return null;
        }

        return Employee::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where(function ($q) use ($phone, $pan, $aadhaar) {
                if ($pan) {
                    $q->orWhere('pan_number', $pan);
                }
                if ($aadhaar) {
                    $q->orWhere('aadhaar_hash', $aadhaar);
                }
                if ($phone && strlen($phone) >= 10) {
                    $q->orWhere('phone', $phone);
                }
            })
            ->first();
    }

    /**
     * Complete candidate onboarding workflow in a single database transaction.
     */
    public function onboardCandidate(int $companyId, array $data, ?\App\Models\User $creator = null): Employee
    {
        $company = \App\Models\Company::findOrFail($companyId);
        $code = $this->generateEmployeeCode($companyId, $company->code, $data['location_id'] ?? null, $data['department_id'] ?? null);

        $aadhaarHash = null;
        $aadhaarMasked = null;
        if (!empty($data['aadhaar_number'])) {
            $aadhaarHash = $this->hashAadhaar($data['aadhaar_number']);
            $aadhaarMasked = $this->maskAadhaar($data['aadhaar_number']);
        }

        return DB::transaction(function () use ($companyId, $code, $data, $aadhaarHash, $aadhaarMasked, $creator) {
            $empType = $data['employee_type'] ?? $data['employment_type'] ?? 'Permanent';
            $confirmStatus = $data['confirmation_status'] ?? ($empType === 'Probationary' ? 'On Probation' : ($empType === 'Intern' ? 'Not Applicable' : 'Confirmed'));
            $initialStatus = $data['initial_employment_status'] ?? ($empType === 'Intern' ? 'Intern' : ($empType === 'Probationary' ? 'Probation' : 'Active'));
            $codeMethod = (!empty($data['manual_employee_code']) && !empty($data['custom_employee_code'])) ? 'Manual' : 'Automatic';
            $finalCode = ($codeMethod === 'Manual') ? trim($data['custom_employee_code']) : $code;

            $emp = Employee::create([
                'company_id' => $companyId,
                'employee_code' => $finalCode,
                'code_generation_method' => $codeMethod,
                'gate_pass_number' => !empty($data['gate_pass_number']) ? trim($data['gate_pass_number']) : null,
                'employment_channel' => $data['employment_channel'] ?? $data['onboarding_channel'] ?? 'Direct',
                'contractor_id' => $data['contractor_id'] ?? null,
                'employee_type' => $empType,
                'initial_employment_status' => $initialStatus,
                'location_id' => $data['location_id'],
                'joining_location_id' => $data['location_id'],
                'department_id' => $data['department_id'],
                'joining_department_id' => $data['department_id'],
                'category_id' => $data['category_id'] ?? $data['employee_category_id'],
                'designation_id' => $data['designation_id'],
                'joining_designation_id' => $data['designation_id'],
                'reporting_manager_id' => $data['reporting_manager_id'] ?? null,
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'email' => $data['email'] ?? null,
                'phone' => preg_replace('/[^\d+]/', '', $data['phone']),
                'gender' => $data['gender'] ?? 'Male',
                'date_of_birth' => $data['date_of_birth'],
                'date_of_joining' => $data['date_of_joining'] ?? $data['joining_date'] ?? now()->toDateString(),
                'probation_start_date' => $data['probation_start_date'] ?? null,
                'probation_end_date' => $data['probation_end_date'] ?? null,
                'confirmation_due_date' => $data['confirmation_due_date'] ?? $data['probation_end_date'] ?? null,
                'confirmation_status' => $confirmStatus,
                'aadhaar_hash' => $aadhaarHash,
                'aadhaar_masked' => $aadhaarMasked,
                'pan_number' => !empty($data['pan_number']) ? strtoupper(trim($data['pan_number'])) : null,
                'address' => $data['address'] ?? null,
                'status' => 'Active',
                'ekyc_status' => 'Pending',
                'created_by' => $creator?->id,
            ]);

            // Lifecycle Event: Employee Created
            \App\Models\EmployeeLifecycleEvent::create([
                'employee_id' => $emp->id,
                'company_id' => $companyId,
                'event_type' => 'Employee Created',
                'previous_value' => null,
                'new_value' => [
                    'employee_code' => $emp->employee_code,
                    'full_name' => $emp->full_name,
                    'employment_type' => $emp->employee_type,
                    'date_of_joining' => $emp->date_of_joining?->format('Y-m-d'),
                    'location_id' => $emp->location_id,
                    'department_id' => $emp->department_id,
                    'designation_id' => $emp->designation_id,
                    'code_generation_method' => $codeMethod,
                ],
                'effective_date' => $emp->date_of_joining?->format('Y-m-d') ?: now()->toDateString(),
                'reason' => 'Workforce Onboarding',
                'remarks' => 'New employee profile established in system.',
                'requested_by' => $creator?->id,
                'approved_by' => $creator?->id,
                'approved_at' => now(),
            ]);

            // Gate pass history
            if (!empty($emp->gate_pass_number)) {
                EmployeeGatePassHistory::create([
                    'employee_id' => $emp->id,
                    'previous_gate_pass' => null,
                    'new_gate_pass' => $emp->gate_pass_number,
                    'reason' => 'Initial workforce gate pass allocation on onboarding',
                    'changed_by' => $creator?->id,
                ]);
            }

            // Check and link previous employments across group entities
            $previousMatches = $this->findPreviousEmployments(
                $aadhaarHash,
                $emp->pan_number,
                $emp->phone,
                $emp->first_name,
                $emp->last_name,
                $emp->date_of_birth->format('Y-m-d')
            );

            foreach ($previousMatches as $prev) {
                if ($prev['previous_employee_id'] !== $emp->id) {
                    EmployeePreviousEmployment::create([
                        'employee_id' => $emp->id,
                        'previous_employee_code' => $prev['previous_employee_code'],
                        'company_name' => $prev['company_name'] ?? 'Group Company',
                        'designation' => $prev['designation'] ?? 'Staff',
                        'from_date' => $prev['joining_date'] ?? now()->toDateString(),
                        'to_date' => now()->toDateString(),
                        'reason_for_leaving' => 'Internal group re-hire / cross-company transfer',
                        'is_internal_company' => true,
                    ]);
                }
            }

            \App\Events\EmployeeCreatedEvent::dispatch($emp);

            return $emp;
        });
    }

    /**
     * Mask raw 12-digit Aadhaar number: XXXX-XXXX-1234
     */
    public function maskAadhaar(string $rawAadhaar): string
    {
        $digits = preg_replace('/\D/', '', $rawAadhaar);
        if (strlen($digits) < 4) {
            return 'XXXX-XXXX-XXXX';
        }
        $last4 = substr($digits, -4);
        return "XXXX-XXXX-{$last4}";
    }

    /**
     * Tokenize raw Aadhaar number using SHA-256 for duplicate matching
     */
    public function hashAadhaar(string $rawAadhaar): string
    {
        $digits = preg_replace('/\D/', '', $rawAadhaar);
        return hash('sha256', $digits);
    }
}
