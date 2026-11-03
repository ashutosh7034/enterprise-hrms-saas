<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\AttendanceCalculationService;
use Illuminate\Console\Command;

class AttendanceCalculateCommand extends Command
{
    protected $signature = 'attendance:calculate
                            {--company= : Company ID or Code}
                            {--date= : Attendance Date (YYYY-MM-DD)}';

    protected $description = 'Trigger attendance calculation engine for a company on a specific date';

    public function handle(AttendanceCalculationService $service): int
    {
        $companyId = $this->option('company');
        if ($companyId) {
            $company = is_numeric($companyId)
                ? Company::find($companyId)
                : Company::where('code', $companyId)->first();
        } else {
            $company = Company::first();
        }

        if (!$company) {
            $this->error("Company not found.");
            return self::FAILURE;
        }

        $date = $this->option('date') ?: now()->toDateString();

        $this->info("Executing Attendance Engine for {$company->name} on {$date}...");
        $processed = $service->calculateCompanyAttendance($company->id, $date);

        $this->info("Completed. Successfully calculated muster for {$processed} active employees.");
        return self::SUCCESS;
    }
}

