<?php

namespace App\Console\Commands;

use App\Models\BiometricDevice;
use App\Models\Company;
use App\Models\Employee;
use App\Services\BiometricIntegrationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BiometricIngestCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'biometric:ingest
                            {--company= : Company ID or Code}
                            {--device= : Device Identifier}
                            {--employee= : Employee Code or Gate Pass Number}
                            {--direction=IN : Punch direction (IN, OUT)}
                            {--time= : Punch timestamp (YYYY-MM-DD HH:MM:SS)}
                            {--simulate : Generate simulated test punches}
                            {--file= : Path to JSON punch payload file}';

    /**
     * The console command description.
     */
    protected $description = 'Ingest raw biometric punches via CLI, simulated feed, or JSON batch';

    public function handle(BiometricIntegrationService $service): int
    {
        $this->info("Initializing biometric punch ingestion...");

        // Resolve company
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

        // Resolve device
        $device = null;
        if ($deviceIdent = $this->option('device')) {
            $device = BiometricDevice::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('device_identifier', $deviceIdent)
                ->first();
        }

        $punches = [];

        // Scenario 1: Ingest from JSON file
        if ($file = $this->option('file')) {
            if (!file_exists($file)) {
                $this->error("Specified file does not exist: {$file}");
                return self::FAILURE;
            }
            $content = file_get_contents($file);
            $punches = json_decode($content, true);
            if (!is_array($punches)) {
                $this->error("Invalid JSON format in {$file}");
                return self::FAILURE;
            }
        }
        // Scenario 2: Simulate or single punch via CLI arguments
        elseif ($empCode = $this->option('employee')) {
            $time = $this->option('time') ?: now()->toDateTimeString();
            $direction = strtoupper($this->option('direction') ?: 'IN');

            $punches[] = [
                'user_id' => $empCode,
                'punch_time' => $time,
                'direction' => $direction,
            ];
        }
        // Scenario 3: Bulk simulated punches for active employees
        elseif ($this->option('simulate')) {
            $employees = Employee::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('status', 'Active')
                ->take(5)
                ->get();

            $today = now()->toDateString();
            foreach ($employees as $emp) {
                // Morning IN
                $punches[] = [
                    'user_id' => $emp->gate_pass_number ?: $emp->employee_code,
                    'punch_time' => "{$today} 08:55:00",
                    'direction' => 'IN',
                ];
                // Evening OUT
                $punches[] = [
                    'user_id' => $emp->gate_pass_number ?: $emp->employee_code,
                    'punch_time' => "{$today} 18:05:00",
                    'direction' => 'OUT',
                ];
            }
        } else {
            $this->error("Please specify --employee, --file, or --simulate.");
            return self::FAILURE;
        }

        $result = $service->ingestPunches(
            companyId: $company->id,
            punches: $punches,
            source: 'CLI Ingestion',
            device: $device
        );

        $this->info("Ingestion completed successfully:");
        $this->table(
            ['Received', 'Processed', 'Duplicates Ignored', 'Failed / Unmapped'],
            [[$result['records_received'], $result['records_processed'], $result['records_duplicate'], $result['records_failed']]]
        );

        return self::SUCCESS;
    }
}

