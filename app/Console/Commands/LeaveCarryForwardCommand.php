<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\LeaveCarryForwardService;
use Illuminate\Console\Command;

class LeaveCarryForwardCommand extends Command
{
    protected $signature = 'leave:carry-forward 
                            {--company= : Company code (e.g. VE, VRE) or ID} 
                            {--year= : Source year to carry forward from} 
                            {--to-year= : Target year to carry forward to} 
                            {--dry-run : Simulate execution without updating balances}';

    protected $description = 'Process year-end leave carry-forward and expiry with optional dry-run preview';

    public function handle(LeaveCarryForwardService $carryForwardService): int
    {
        $companyCode = $this->option('company');
        $fromYear = (int) ($this->option('year') ?? now()->subYear()->format('Y'));
        $toYear = (int) ($this->option('to-year') ?? ($fromYear + 1));
        $dryRun = (bool) $this->option('dry-run');

        $this->info("Initiating Leave Carry-Forward Engine...");
        $this->info("From Year: {$fromYear} -> To Year: {$toYear}");
        $this->info("Dry-Run Mode: " . ($dryRun ? 'ENABLED (Simulation Only)' : 'DISABLED (Live Database Execution)'));

        $query = Company::query();
        if ($companyCode) {
            $query->where(function ($q) use ($companyCode) {
                $q->where('code', $companyCode)
                  ->orWhere('id', $companyCode);
            });
        }

        $companies = $query->get();

        if ($companies->isEmpty()) {
            $this->error("No matching company found.");
            return 1;
        }

        foreach ($companies as $company) {
            $this->info("Processing Company: {$company->name} ({$company->code})...");

            $result = $carryForwardService->processYearEnd(
                companyId: $company->id,
                fromYear: $fromYear,
                toYear: $toYear,
                leaveTypeId: null,
                dryRun: $dryRun,
                actor: null
            );

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Status', $result['is_dry_run'] ? 'Simulated' : 'Processed'],
                    ['Batch Number', $result['batch']->batch_number],
                    ['Employees Evaluated', $result['total_processed']],
                    ['Total Days Carried Forward', $result['total_carried_forward']],
                    ['Total Days Expired', $result['total_expired']],
                ]
            );

            if (!empty($result['log'])) {
                $this->info("Sample breakdown (first 10 records):");
                $sample = array_slice($result['log'], 0, 10);
                $this->table(
                    ['Emp Code', 'Name', 'Leave Type', 'Previous Closing', 'Carried Forward', 'Expired'],
                    array_map(fn($row) => [
                        $row['employee_code'],
                        $row['employee_name'],
                        $row['leave_type'],
                        $row['previous_closing'],
                        $row['carried_forward'],
                        $row['expired']
                    ], $sample)
                );
            }
        }

        $this->info("Carry-forward processing completed successfully.");
        return 0;
    }
}

