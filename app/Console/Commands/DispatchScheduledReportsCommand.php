<?php

namespace App\Console\Commands;

use App\Models\ScheduledReport;
use App\Models\ScheduledReportLog;
use App\Models\User;
use App\Services\CompanyContextService;
use App\Services\NotificationService;
use App\Services\Reporting\ReportingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DispatchScheduledReportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'reports:dispatch-scheduled 
                            {--report-id= : Optional specific scheduled report ID to run}
                            {--force : Run immediately bypassing next_run_at schedule check}';

    /**
     * The console command description.
     */
    protected $description = 'Execute active scheduled HRMS reports and dispatch output to configured recipients';

    public function __construct(
        protected ReportingService $reportingService,
        protected CompanyContextService $contextService,
        protected NotificationService $notificationService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("Initiating Scheduled Reports Dispatch Engine...");

        $query = ScheduledReport::where('is_active', true);

        if ($reportId = $this->option('report-id')) {
            $query->where('id', $reportId);
        } elseif (!$this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('next_run_at')
                  ->orWhere('next_run_at', '<=', now());
            });
        }

        $reports = $query->with(['company', 'creator'])->get();

        if ($reports->isEmpty()) {
            $this->info("No due scheduled reports found.");
            return self::SUCCESS;
        }

        $this->info("Processing {$reports->count()} scheduled report(s)...");

        $dispatched = 0;
        $failed = 0;

        foreach ($reports as $report) {
            $this->line("-> Processing: [{$report->id}] '{$report->name}' ({$report->report_type}) for Company ID {$report->company_id}");

            try {
                // Set active company context
                $this->contextService->setActiveCompanyId($report->company_id);

                // Generate report data
                $data = $this->reportingService->generateReport(
                    reportType: $report->report_type,
                    filters: $report->filters_json ?? [],
                    actor: $report->creator
                );

                // Format and store report file
                $format = strtolower($report->export_format ?? 'csv');
                $timestamp = now()->format('Ymd_His');
                $filename = "scheduled_reports/{$report->company_id}/report_{$report->id}_{$timestamp}.{$format}";

                $content = $this->compileReportContent($data, $format);
                Storage::disk('local')->put($filename, $content);

                // Count recipients
                $recipients = $report->recipients_json ?? [];
                $recipientCount = count($recipients);

                // Dispatch notification
                $this->notifyRecipients($report, $filename, $recipients);

                // Calculate next run date
                $nextRun = $this->calculateNextRun($report->frequency);

                $report->update([
                    'last_run_at' => now(),
                    'next_run_at' => $nextRun,
                ]);

                // Create audit log
                ScheduledReportLog::create([
                    'scheduled_report_id' => $report->id,
                    'status' => 'Success',
                    'recipients_count' => $recipientCount,
                    'file_path' => $filename,
                    'dispatched_at' => now(),
                ]);

                $this->info("   [SUCCESS] Dispatched to {$recipientCount} recipient(s). Next run: {$nextRun->toDateTimeString()}");
                $dispatched++;
            } catch (Throwable $e) {
                $this->error("   [FAILED] Error: " . $e->getMessage());

                ScheduledReportLog::create([
                    'scheduled_report_id' => $report->id,
                    'status' => 'Failed',
                    'recipients_count' => 0,
                    'error_message' => substr($e->getMessage(), 0, 1000),
                    'dispatched_at' => now(),
                ]);

                $failed++;
            }
        }

        $this->info("Scheduled Reports Dispatch completed. Dispatched: {$dispatched}, Failed: {$failed}.");
        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function compileReportContent(array $data, string $format): string
    {
        $handle = fopen('php://temp', 'r+');
        fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

        if (!empty($data['headers'])) {
            fputcsv($handle, $data['headers']);
        }
        foreach ($data['rows'] ?? [] as $row) {
            fputcsv($handle, array_values($row));
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    protected function calculateNextRun(string $frequency): Carbon
    {
        return match (strtolower($frequency)) {
            'daily' => now()->addDay()->setTime(6, 0),
            'weekly' => now()->addWeek()->startOfWeek()->setTime(6, 0),
            default => now()->addMonth()->startOfMonth()->setTime(6, 0),
        };
    }

    protected function notifyRecipients(ScheduledReport $report, string $filePath, array $recipients): void
    {
        foreach ($recipients as $recipient) {
            $user = is_numeric($recipient)
                ? User::find($recipient)
                : User::where('email', $recipient)->first();

            if ($user) {
                try {
                    $this->notificationService->sendDirect(
                        user: $user,
                        category: 'HR_OPERATIONS',
                        title: "Scheduled Report Ready: {$report->name}",
                        body: "Your scheduled {$report->frequency} report ({$report->report_type}) has been generated and is ready for download.",
                        actionUrl: "/admin/reports/{$report->report_type}",
                        channels: ['in_app', 'email']
                    );
                } catch (Throwable $e) {
                    // Suppress individual delivery exceptions so job completes
                }
            }
        }
    }
}

