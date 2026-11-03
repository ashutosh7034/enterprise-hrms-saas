<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Location;
use App\Models\PayrollRun;
use App\Models\ReportExportHistory;
use App\Models\ScheduledReport;
use App\Services\CompanyContextService;
use App\Services\Reporting\ReportingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class ReportController extends Controller
{
    public function __construct(
        protected ReportingService $reportingService,
        protected CompanyContextService $contextService
    ) {}

    /**
     * Central Reporting Hub: Categorized catalog of all 53 HRMS enterprise reports.
     */
    public function index(Request $request)
    {
        $actor = auth()->user();
        $catalog = $this->reportingService->getReportCatalog();

        // Filter catalog based on user's RBAC role
        $accessibleCatalog = [];
        foreach ($catalog as $category => $reports) {
            $filtered = [];
            foreach ($reports as $code => $meta) {
                if ($this->reportingService->canUserAccessReport($actor, $code)) {
                    $filtered[$code] = $meta;
                }
            }
            if (!empty($filtered)) {
                $accessibleCatalog[$category] = $filtered;
            }
        }

        $recentExports = ReportExportHistory::where('company_id', $this->contextService->getActiveCompanyId())
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $activeSchedulesCount = ScheduledReport::where('company_id', $this->contextService->getActiveCompanyId())
            ->where('is_active', true)
            ->count();

        return view('reports.index', compact('accessibleCatalog', 'recentExports', 'activeSchedulesCount'));
    }

    /**
     * Dedicated Report Viewer with multi-parameter filter builder.
     */
    public function show(Request $request, string $type)
    {
        $actor = auth()->user();
        $this->reportingService->guardReportAccess($actor, $type);

        $companyId = $this->contextService->getActiveCompanyId();
        $filters = $request->only([
            'department_id',
            'designation_id',
            'location_id',
            'category_id',
            'employee_id',
            'employment_status',
            'date_from',
            'date_to',
            'date',
            'year',
            'month',
            'payroll_run_id',
        ]);

        $reportData = $this->reportingService->generateReport($type, $filters, $actor);

        // Load filter master datasets
        $departments = Department::where('company_id', $companyId)->orderBy('name')->get();
        $designations = Designation::orderBy('title')->get();
        $locations = Location::where('company_id', $companyId)->orderBy('name')->get();
        $categories = Category::where('company_id', $companyId)->orderBy('name')->get();
        $payrollRuns = PayrollRun::where('company_id', $companyId)->orderByDesc('id')->limit(12)->get();

        return view('reports.view', compact(
            'type',
            'reportData',
            'filters',
            'departments',
            'designations',
            'locations',
            'categories',
            'payrollRuns'
        ));
    }

    /**
     * Multi-Format Exporter (CSV, Excel, PDF).
     */
    public function export(Request $request, string $type)
    {
        $actor = auth()->user();
        $this->reportingService->guardReportAccess($actor, $type);

        $format = strtolower($request->query('format', 'csv'));
        $filters = $request->except(['format', '_token']);

        $reportData = $this->reportingService->generateReport($type, $filters, $actor);

        return match ($format) {
            'pdf' => $this->reportingService->renderPdf($type, $reportData, $actor),
            'excel', 'xlsx' => $this->reportingService->streamExcel($type, $reportData, $actor),
            default => $this->reportingService->streamCsv($type, $reportData, $actor),
        };
    }

    /**
     * Bank Transfer Advice (NEFT / RTGS) Dedicated View.
     */
    public function bankAdvice(Request $request)
    {
        $actor = auth()->user();
        $this->reportingService->guardReportAccess($actor, 'BANK_TRANSFER_ADVICE');

        $companyId = $this->contextService->getActiveCompanyId();
        $payrollRuns = PayrollRun::where('company_id', $companyId)->orderByDesc('id')->get();
        $activeRunId = $request->query('payroll_run_id', $payrollRuns->first()?->id);

        $filters = ['payroll_run_id' => $activeRunId];
        $reportData = $this->reportingService->generateReport('BANK_TRANSFER_ADVICE', $filters, $actor);

        $canViewUnmasked = $actor->hasRole('ho_admin') || ($actor->role?->name === 'finance');

        return view('reports.bank_advice', compact('reportData', 'payrollRuns', 'activeRunId', 'canViewUnmasked'));
    }

    /**
     * Scheduled Reports Management Console.
     */
    public function schedules(Request $request)
    {
        $actor = auth()->user();
        if (!$actor->hasAnyRole(['ho_admin', 'hr_executive'])) {
            abort(403, 'Unauthorized access to scheduled reports management.');
        }

        $companyId = $this->contextService->getActiveCompanyId();
        $schedules = ScheduledReport::where('company_id', $companyId)
            ->with(['creator', 'logs'])
            ->orderByDesc('id')
            ->paginate(15);

        $catalog = $this->reportingService->getReportCatalog();

        return view('reports.schedules', compact('schedules', 'catalog'));
    }

    /**
     * Store a newly scheduled report configuration.
     */
    public function storeSchedule(Request $request)
    {
        $actor = auth()->user();
        if (!$actor->hasAnyRole(['ho_admin', 'hr_executive'])) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'report_type' => 'required|string|max:100',
            'frequency' => 'required|in:Daily,Weekly,Monthly',
            'export_format' => 'required|in:csv,xlsx,pdf',
            'recipients' => 'required|string',
        ]);

        $companyId = $this->contextService->getActiveCompanyId();
        $recipientsArray = array_filter(array_map('trim', explode(',', $validated['recipients'])));

        $nextRun = match ($validated['frequency']) {
            'Daily' => now()->addDay()->setTime(6, 0),
            'Weekly' => now()->addWeek()->startOfWeek()->setTime(6, 0),
            default => now()->addMonth()->startOfMonth()->setTime(6, 0),
        };

        ScheduledReport::create([
            'company_id' => $companyId,
            'name' => $validated['name'],
            'report_type' => $validated['report_type'],
            'frequency' => $validated['frequency'],
            'export_format' => $validated['export_format'],
            'recipients_json' => $recipientsArray,
            'filters_json' => [],
            'is_active' => true,
            'next_run_at' => $nextRun,
            'created_by' => $actor->id,
        ]);

        return redirect()->route('admin.reports.schedules')->with('success', 'Scheduled report successfully registered.');
    }

    /**
     * Run a scheduled report immediately.
     */
    public function runScheduleNow(ScheduledReport $schedule)
    {
        $actor = auth()->user();
        if (!$actor->hasAnyRole(['ho_admin', 'hr_executive'])) {
            abort(403);
        }

        try {
            Artisan::call('reports:dispatch-scheduled', [
                '--report-id' => $schedule->id,
                '--force' => true,
            ]);

            return back()->with('success', "Report '{$schedule->name}' was executed successfully. Output dispatched to recipients.");
        } catch (Throwable $e) {
            return back()->with('error', "Execution failed: " . $e->getMessage());
        }
    }

    /**
     * Toggle active status of a scheduled report.
     */
    public function toggleSchedule(ScheduledReport $schedule)
    {
        $actor = auth()->user();
        if (!$actor->hasAnyRole(['ho_admin', 'hr_executive'])) {
            abort(403);
        }

        $schedule->update(['is_active' => !$schedule->is_active]);

        return back()->with('success', "Scheduled report status updated to " . ($schedule->is_active ? 'Active' : 'Inactive'));
    }

    /**
     * Delete a scheduled report.
     */
    public function destroySchedule(ScheduledReport $schedule)
    {
        $actor = auth()->user();
        if (!$actor->hasAnyRole(['ho_admin', 'hr_executive'])) {
            abort(403);
        }

        $schedule->delete();

        return back()->with('success', 'Scheduled report deleted.');
    }

    /**
     * Report Export History Audit Trail.
     */
    public function history(Request $request)
    {
        $companyId = $this->contextService->getActiveCompanyId();
        $exports = ReportExportHistory::where('company_id', $companyId)
            ->with(['user'])
            ->orderByDesc('id')
            ->paginate(20);

        return view('reports.history', compact('exports'));
    }
}
