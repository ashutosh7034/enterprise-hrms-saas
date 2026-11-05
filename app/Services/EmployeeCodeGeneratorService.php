<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeCodeConfiguration;
use App\Models\EmployeeCodeSequence;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

class EmployeeCodeGeneratorService
{
    /**
     * Get or create active code configuration for a company.
     */
    public function getConfiguration(int $companyId): EmployeeCodeConfiguration
    {
        $config = EmployeeCodeConfiguration::where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if (!$config) {
            $company = Company::find($companyId);
            $compCode = $company?->code ?? 'VE';

            $config = EmployeeCodeConfiguration::create([
                'company_id' => $companyId,
                'prefix' => 'EMP',
                'company_prefix' => $compCode,
                'branch_prefix' => null,
                'department_prefix' => null,
                'financial_year_prefix' => date('Y'),
                'number_length' => 4,
                'starting_sequence_number' => 1001,
                'separator' => '-',
                'format_template' => '{COMPANY}-{PREFIX}-{SEQUENCE}',
                'reset_sequence_annually' => false,
                'reset_sequence_per_branch' => false,
                'reset_sequence_per_company' => true,
                'allow_manual_employee_code' => false,
                'uniqueness_scope' => 'company',
                'is_active' => true,
            ]);
        }

        return $config;
    }

    /**
     * Generate preview of code template without incrementing sequence.
     */
    public function previewCode(int $companyId, array $attributes = [], ?array $configOverrides = null): string
    {
        $config = $this->getConfiguration($companyId);
        
        $template = $configOverrides['format_template'] ?? $config->format_template;
        $separator = $configOverrides['separator'] ?? $config->separator;
        $numLength = (int) ($configOverrides['number_length'] ?? $config->number_length);
        $prefix = $configOverrides['prefix'] ?? $config->prefix;

        $company = Company::find($companyId);
        $companyPrefix = $configOverrides['company_prefix'] ?? ($config->company_prefix ?: ($company?->code ?? 'VE'));

        // Location / Branch code
        $branchCode = 'CORP';
        if (!empty($attributes['location_id'])) {
            $loc = Location::find($attributes['location_id']);
            $branchCode = $loc?->code ?: ($configOverrides['branch_prefix'] ?? 'SITE');
        } elseif (!empty($configOverrides['branch_prefix'])) {
            $branchCode = $configOverrides['branch_prefix'];
        }

        // Department code
        $deptCode = 'GEN';
        if (!empty($attributes['department_id'])) {
            $dept = Department::find($attributes['department_id']);
            $deptCode = $dept?->code ?: ($configOverrides['department_prefix'] ?? 'DEPT');
        } elseif (!empty($configOverrides['department_prefix'])) {
            $deptCode = $configOverrides['department_prefix'];
        }

        // Financial Year
        $fy = $this->getFinancialYear();

        // Sample sequence
        $sampleSeq = str_pad((string) ($configOverrides['starting_sequence_number'] ?? $config->starting_sequence_number), $numLength, '0', STR_PAD_LEFT);

        return $this->parseTemplate($template, [
            '{COMPANY}' => $companyPrefix,
            '{BRANCH}' => $branchCode,
            '{DEPARTMENT}' => $deptCode,
            '{DEPT}' => $deptCode,
            '{FY}' => $fy,
            '{PREFIX}' => $prefix,
            '{SEQUENCE}' => $sampleSeq,
        ], $separator);
    }

    /**
     * Concurrency-safe atomic generation of next employee code.
     */
    public function generateCode(int $companyId, array $attributes = []): string
    {
        return $this->generateNextCode(
            $companyId,
            $attributes['location_id'] ?? null,
            $attributes['department_id'] ?? null
        );
    }

    /**
     * Concurrency-safe atomic generation of next employee code.
     */
    public function generateNextCode(int $companyId, ?int $locationId = null, ?int $departmentId = null): string
    {
        return DB::transaction(function () use ($companyId, $locationId, $departmentId) {
            $config = $this->getConfiguration($companyId);

            $company = Company::find($companyId);
            $companyPrefix = $config->company_prefix ?: ($company?->code ?? 'VE');

            $branchCode = '';
            if ($locationId) {
                $loc = Location::find($locationId);
                $branchCode = $loc?->code ?? ($config->branch_prefix ?? 'CORP');
            } else {
                $branchCode = $config->branch_prefix ?? 'CORP';
            }

            $deptCode = '';
            if ($departmentId) {
                $dept = Department::find($departmentId);
                $deptCode = $dept?->code ?? ($config->department_prefix ?? 'GEN');
            } else {
                $deptCode = $config->department_prefix ?? 'GEN';
            }

            $fy = $this->getFinancialYear();

            // Build atomic sequence key based on reset configuration
            $keyParts = ["COMP_{$companyId}"];
            if ($config->reset_sequence_per_company) {
                // company specific
            }
            if ($config->reset_sequence_per_branch && $branchCode) {
                $keyParts[] = "BR_{$branchCode}";
            }
            if ($config->reset_sequence_annually) {
                $keyParts[] = "FY_{$fy}";
            }
            $sequenceKey = implode('_', $keyParts);

            // Pessimistic lock on sequence record
            $seqRecord = EmployeeCodeSequence::where('company_id', $companyId)
                ->where('sequence_key', $sequenceKey)
                ->lockForUpdate()
                ->first();

            if (!$seqRecord) {
                // Find existing max number from database if this is the first initialization
                $existingMax = $this->detectExistingMaxSequence($companyId, $companyPrefix);
                $initialNum = max($config->starting_sequence_number, $existingMax + 1);

                $seqRecord = EmployeeCodeSequence::create([
                    'company_id' => $companyId,
                    'sequence_key' => $sequenceKey,
                    'last_number' => $initialNum,
                ]);

                $nextNumber = $initialNum;
            } else {
                $nextNumber = $seqRecord->last_number + 1;
                $seqRecord->update(['last_number' => $nextNumber]);
            }

            $seqString = str_pad((string) $nextNumber, $config->number_length, '0', STR_PAD_LEFT);

            $code = $this->parseTemplate($config->format_template, [
                '{COMPANY}' => $companyPrefix,
                '{BRANCH}' => $branchCode,
                '{DEPARTMENT}' => $deptCode,
                '{DEPT}' => $deptCode,
                '{FY}' => $fy,
                '{PREFIX}' => $config->prefix,
                '{SEQUENCE}' => $seqString,
            ], $config->separator);

            // Guarantee uniqueness within scope
            while (!$this->validateCodeUniqueness($code, $companyId, $config->uniqueness_scope)) {
                $nextNumber++;
                $seqRecord->update(['last_number' => $nextNumber]);
                $seqString = str_pad((string) $nextNumber, $config->number_length, '0', STR_PAD_LEFT);
                $code = $this->parseTemplate($config->format_template, [
                    '{COMPANY}' => $companyPrefix,
                    '{BRANCH}' => $branchCode,
                    '{DEPARTMENT}' => $deptCode,
                    '{DEPT}' => $deptCode,
                    '{FY}' => $fy,
                    '{PREFIX}' => $config->prefix,
                    '{SEQUENCE}' => $seqString,
                ], $config->separator);
            }

            return $code;
        });
    }

    /**
     * Validate code uniqueness according to uniqueness scope.
     */
    public function validateCodeUniqueness(string $code, int $companyId, string $scope = 'company', ?int $excludeEmployeeId = null): bool
    {
        $query = Employee::withoutGlobalScopes()->where('employee_code', $code);

        if ($excludeEmployeeId) {
            $query->where('id', '!=', $excludeEmployeeId);
        }

        if ($scope === 'company') {
            $query->where('company_id', $companyId);
        } elseif ($scope === 'tenant') {
            $company = Company::find($companyId);
            if ($company && $company->tenant_id) {
                $tenantCompanyIds = Company::where('tenant_id', $company->tenant_id)->pluck('id');
                $query->whereIn('company_id', $tenantCompanyIds);
            }
        }

        return !$query->exists();
    }

    /**
     * Detect current highest numeric suffix across existing records.
     */
    protected function detectExistingMaxSequence(int $companyId, string $companyPrefix): int
    {
        $latestCodes = Employee::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->orderBy('id', 'desc')
            ->limit(100)
            ->pluck('employee_code');

        $max = 0;
        foreach ($latestCodes as $c) {
            if (preg_match('/(\d{3,})/', $c, $m)) {
                $val = (int) $m[1];
                if ($val > $max) {
                    $max = $val;
                }
            }
        }

        return $max;
    }

    /**
     * Formats financial year string (e.g. 2026 or 2026-27).
     */
    public function getFinancialYear(): string
    {
        $year = (int) date('Y');
        $month = (int) date('n');
        // Indian FY starts in April
        return $month >= 4 ? (string) $year : (string) ($year - 1);
    }

    /**
     * Parse template string with token dictionary.
     */
    protected function parseTemplate(string $template, array $tokens, string $separator): string
    {
        $res = str_replace(array_keys($tokens), array_values($tokens), $template);

        // Normalize separator if different from hyphen
        if ($separator !== '-') {
            $res = str_replace('-', $separator, $res);
        }

        // Clean up accidental double separators
        if ($separator) {
            $quoted = preg_quote($separator, '/');
            $res = preg_replace("/{$quoted}+/", $separator, $res);
            $res = trim($res, $separator);
        }

        return $res;
    }
}

