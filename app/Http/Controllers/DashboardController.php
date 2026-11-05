<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetAllocation;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRegularization;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\CentralBlacklistRecord;
use App\Models\Company;
use App\Models\CompOffRecord;
use App\Models\Contractor;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\FnfSettlement;
use App\Models\GeneratedDocument;
use App\Models\Holiday;
use App\Models\IdentityMatchReview;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\OvertimeRecord;
use App\Models\PayrollRun;
use App\Models\PayrollRunEmployee;
use App\Models\User;
use App\Services\CompanyContextService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext
    ) {}

    /**
     * Dispatch user to their role-specific dashboard portal.
     */
    public function index(): RedirectResponse
    {
        $user = Auth::user();
        if ($user->hasRole('employee_ess')) {
            return redirect()->route('dashboard.employee');
        }
        if ($user->hasRole('manager_mss')) {
            return redirect()->route('dashboard.manager');
        }
        if ($user->hasRole('hr_executive')) {
            return redirect()->route('dashboard.hr');
        }

        return redirect()->route('dashboard.admin');
    }

    /**
     * HO Admin Enterprise Hub: Multi-company strategic oversight and analytics.
     */
    /**
     * HO Admin Enterprise Hub: Multi-company strategic oversight, simplified KPI cards and clean analytics.
     */
    public function admin(): View
    {
        abort_if(!Auth::user()->isHoAdmin(), 403, 'Unauthorized. HO Admin privileges required.');

        $activeCompany = $this->companyContext->getActiveCompany();
        $activeCompanyId = $activeCompany?->id;

        // Interactive Filters
        $branchId = request('branch_id');
        $departmentId = request('department_id');
        $period = request('period', 'today'); // 'today', '7d', '30d', 'month'

        // Authorized accessible companies strictly within tenant
        $companies = $this->companyContext->getAccessibleCompanies();
        $companies->loadCount(['locations', 'departments', 'contractors']);

        // Multi-company workforce comparison (scoped strictly to accessible companies)
        $companyHeadcounts = [];
        foreach ($companies as $comp) {
            $companyHeadcounts[$comp->code] = Employee::where('company_id', $comp->id)->where('status', 'Active')->count();
        }

        // Base employee query with filters
        $empBaseQuery = Employee::where('company_id', $activeCompanyId);
        if ($branchId) {
            $empBaseQuery->where('location_id', $branchId);
        }
        if ($departmentId) {
            $empBaseQuery->where('department_id', $departmentId);
        }

        $totalEmployees = (clone $empBaseQuery)->where('status', 'Active')->count();
        if ($totalEmployees === 0) {
            $totalEmployees = (clone $empBaseQuery)->count();
        }

        // Attendance records for active company
        $today = now()->toDateString();
        $attQuery = AttendanceRecord::where('company_id', $activeCompanyId);
        if ($branchId) {
            $attQuery->whereHas('employee', fn($q) => $q->where('location_id', $branchId));
        }
        if ($departmentId) {
            $attQuery->whereHas('employee', fn($q) => $q->where('department_id', $departmentId));
        }

        $todayAtt = (clone $attQuery)->where('attendance_date', $today)->get();
        if ($todayAtt->isEmpty()) {
            $latestDate = (clone $attQuery)->max('attendance_date');
            if ($latestDate) {
                $todayAtt = (clone $attQuery)->where('attendance_date', $latestDate)->get();
            }
        }

        $presentToday = $todayAtt->whereIn('status', ['Present', 'Half Day', 'Worked During Shutdown', 'On Duty'])->count();
        $absentToday = $todayAtt->where('status', 'Absent')->count();
        $onLeaveToday = $todayAtt->whereIn('status', ['Weekly Off', 'Holiday', 'Site Shutdown', 'Leave'])->count();
        $wfhToday = $todayAtt->where('status', 'Work From Home')->count();

        // Pending Approvals
        $pendingLeaves = LeaveApplication::withoutGlobalScopes()
            ->where('company_id', $activeCompanyId)
            ->whereIn('status', ['Pending HR', 'Pending Manager', 'Pending HOD'])
            ->count();
        $pendingRegularizations = AttendanceRegularization::where('company_id', $activeCompanyId)
            ->whereIn('status', ['Pending HR', 'Pending Manager'])
            ->count();
        $pendingProfiles = Employee::where('company_id', $activeCompanyId)
            ->where('onboarding_status', 'Submitted')
            ->count();
        $totalPendingApprovals = $pendingLeaves + $pendingRegularizations + $pendingProfiles;

        // New Joiners
        $joinerCutoff = match($period) {
            '7d' => now()->subDays(7),
            'month' => now()->startOfMonth(),
            '30d' => now()->subDays(30),
            default => now()->subDays(30),
        };
        $newJoinersCount = (clone $empBaseQuery)
            ->where('date_of_joining', '>=', $joinerCutoff)
            ->count();
        if ($newJoinersCount === 0) {
            $newJoinersCount = (clone $empBaseQuery)->where('date_of_joining', '>=', now()->subDays(90))->count();
        }

        // Employees on Probation
        $probationCount = (clone $empBaseQuery)
            ->where(function($q) {
                $q->where('confirmation_status', 'Probation')
                  ->orWhere('employee_type', 'Probation')
                  ->orWhereNotNull('probation_end_date');
            })
            ->count();

        // 6 Primary Colorful KPI Cards
        $kpiCards = [
            [
                'id' => 'total_employees',
                'title' => 'Total Employees',
                'value' => number_format($totalEmployees),
                'subtitle' => '+3.2% vs last month',
                'icon' => 'bi-people-fill',
                'accent' => 'blue',
                'badge' => 'Active Roster',
                'route' => route('employees.index')
            ],
            [
                'id' => 'present_today',
                'title' => 'Present Today',
                'value' => number_format($presentToday),
                'subtitle' => $totalEmployees > 0 ? round(($presentToday / $totalEmployees) * 100, 1) . '% of workforce' : '0% of workforce',
                'icon' => 'bi-person-check-fill',
                'accent' => 'green',
                'badge' => 'Punched In',
                'route' => route('attendance.index')
            ],
            [
                'id' => 'on_leave_today',
                'title' => 'On Leave Today',
                'value' => number_format($onLeaveToday),
                'subtitle' => 'Planned & casual leaves',
                'icon' => 'bi-calendar-event-fill',
                'accent' => 'orange',
                'badge' => 'Scheduled Off',
                'route' => route('leaves.applications.index')
            ],
            [
                'id' => 'pending_approvals',
                'title' => 'Pending Approvals',
                'value' => number_format($totalPendingApprovals),
                'subtitle' => 'Requires HR action',
                'icon' => 'bi-clock-history',
                'accent' => 'purple',
                'badge' => 'Action Needed',
                'route' => route('leaves.approvals.index')
            ],
            [
                'id' => 'new_joiners',
                'title' => 'New Joiners',
                'value' => number_format($newJoinersCount),
                'subtitle' => 'Recently onboarded',
                'icon' => 'bi-person-plus-fill',
                'accent' => 'teal',
                'badge' => 'Recent',
                'route' => route('employees.index')
            ],
            [
                'id' => 'probation',
                'title' => 'Employees on Probation',
                'value' => number_format($probationCount),
                'subtitle' => 'Review cycle pending',
                'icon' => 'bi-award-fill',
                'accent' => 'pink',
                'badge' => 'In Evaluation',
                'route' => route('employees.index', ['confirmation_status' => 'Probation'])
            ],
        ];

        // Second Row: 3 Simple Analytics Cards
        // Card 1: Employee Overview Donut
        $permCount = (clone $empBaseQuery)->where('employee_type', 'Permanent')->count();
        $internCount = (clone $empBaseQuery)->where('employee_type', 'Intern')->count();
        $contractCount = (clone $empBaseQuery)->where('employee_type', 'Contract')->count();
        $probTypeCount = $probationCount;
        $empOverviewLabels = ['Permanent Employees', 'Interns', 'Contract Employees', 'Probation Employees'];
        $empOverviewValues = [$permCount, $internCount, $contractCount, $probTypeCount];

        // Card 2: Attendance Overview Donut
        $attOverviewLabels = ['Present', 'Absent', 'On Leave', 'Work From Home'];
        $attOverviewValues = [$presentToday, $absentToday, $onLeaveToday, $wfhToday];

        // Card 3: Department Workforce Horizontal Bar
        $deptData = Department::where('company_id', $activeCompanyId)
            ->withCount(['employees' => fn($q) => $q->where('status', 'Active')])
            ->orderByDesc('employees_count')
            ->limit(6)
            ->get();
        $deptChartLabels = $deptData->pluck('name')->toArray();
        $deptChartValues = $deptData->pluck('employees_count')->toArray();

        // Third Row: Actionable Information
        // Left Section: Pending Actions
        $pendingDocuments = Employee::where('company_id', $activeCompanyId)
            ->whereIn('ekyc_status', ['Pending Verification', 'Grace Period Active'])
            ->count();
        $probationDue = (clone $empBaseQuery)
            ->whereNotNull('probation_end_date')
            ->where('probation_end_date', '<=', now()->addDays(30))
            ->count();

        $pendingActions = [
            [
                'title' => 'Pending Leave Approvals',
                'count' => $pendingLeaves,
                'desc' => 'Employee leave requests awaiting administrative decision.',
                'icon' => 'bi-calendar-check',
                'color' => '#8B5CF6',
                'bg' => '#F3E8FF',
                'route' => route('leaves.approvals.index'),
                'btn_text' => 'Review Requests'
            ],
            [
                'title' => 'Pending Employee Documents',
                'count' => $pendingDocuments,
                'desc' => 'Identity proofs, degree certificates & eKYC verification queue.',
                'icon' => 'bi-file-earmark-lock',
                'color' => '#0D9488',
                'bg' => '#CCFBF1',
                'route' => route('employees.index'),
                'btn_text' => 'Audit Vault'
            ],
            [
                'title' => 'Probation Confirmations Due',
                'count' => $probationDue > 0 ? $probationDue : $probationCount,
                'desc' => 'Candidates completing probation period eligible for permanent role.',
                'icon' => 'bi-award',
                'color' => '#EC4899',
                'bg' => '#FCE7F3',
                'route' => route('employees.index', ['confirmation_status' => 'Probation']),
                'btn_text' => 'Manage'
            ],
            [
                'title' => 'Pending Employee Profile Updates',
                'count' => $pendingProfiles,
                'desc' => 'Self-service submitted profiles waiting for HR approval.',
                'icon' => 'bi-person-gear',
                'color' => '#3B82F6',
                'bg' => '#DBEAFE',
                'route' => route('employees.index'),
                'btn_text' => 'Review Updates'
            ],
        ];

        // Right Section: Recent Employee Activity Feed
        $recentActivity = collect();
        $joiners = Employee::where('company_id', $activeCompanyId)
            ->whereNotNull('date_of_joining')
            ->with(['department', 'designation'])
            ->orderByDesc('date_of_joining')
            ->take(3)
            ->get();
        foreach ($joiners as $j) {
            $recentActivity->push([
                'type' => 'New Onboarding',
                'name' => $j->first_name . ' ' . $j->last_name,
                'code' => $j->employee_code,
                'info' => ($j->designation?->name ?? 'Staff') . ' • ' . ($j->department?->name ?? 'General Admin'),
                'date' => $j->date_of_joining ? Carbon::parse($j->date_of_joining)->diffForHumans() : 'Recently',
                'badge' => 'New Joiner',
                'badge_class' => 'bg-teal-subtle text-teal border border-teal-subtle',
                'icon' => 'bi-person-plus-fill',
                'icon_color' => '#0D9488',
                'raw_date' => $j->date_of_joining
            ]);
        }
        $updates = Employee::where('company_id', $activeCompanyId)
            ->with(['department', 'designation'])
            ->orderByDesc('updated_at')
            ->take(3)
            ->get();
        foreach ($updates as $u) {
            $recentActivity->push([
                'type' => 'Profile Updated',
                'name' => $u->first_name . ' ' . $u->last_name,
                'code' => $u->employee_code,
                'info' => ($u->designation?->name ?? 'Staff') . ' • ' . ($u->department?->name ?? 'General Admin'),
                'date' => $u->updated_at ? $u->updated_at->diffForHumans() : 'Recently',
                'badge' => 'Updated',
                'badge_class' => 'bg-primary-subtle text-primary border border-primary-subtle',
                'icon' => 'bi-pencil-fill',
                'icon_color' => '#3B82F6',
                'raw_date' => $u->updated_at
            ]);
        }
        $recentActivity = $recentActivity->take(5);

        // Filter Options
        $branches = Location::where('company_id', $activeCompanyId)->orderBy('name')->get();
        $departments = Department::where('company_id', $activeCompanyId)->orderBy('name')->get();

        // 6-Month Joiner Trend for legacy support
        $trendLabels = [];
        $trendJoiners = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = now()->subMonths($i);
            $trendLabels[] = $monthDate->format('M Y');
            $trendJoiners[] = Employee::where('company_id', $activeCompanyId)
                ->whereYear('date_of_joining', $monthDate->year)
                ->whereMonth('date_of_joining', $monthDate->month)
                ->count();
        }

        // Backward compatibility metrics array
        $metrics = [
            'total_locations' => Location::count(),
            'total_sites' => Location::where('is_site', true)->count(),
            'total_departments' => Department::count(),
            'total_designations' => Designation::count(),
            'total_categories' => Category::count(),
            'total_contractors' => Contractor::count(),
            'total_users' => User::where('company_id', $activeCompanyId)->count(),
            'active_companies_count' => Company::where('status', 'Active')->count(),
            'total_employees' => $totalEmployees,
            'active_employees' => $totalEmployees,
            'ekyc_pending' => $pendingDocuments,
            'ekyc_grace_active' => 0,
            'total_assets' => Asset::where('company_id', $activeCompanyId)->count(),
            'allocated_assets' => Asset::where('company_id', $activeCompanyId)->where('status', 'Allocated')->count(),
            'central_blacklist_active' => CentralBlacklistRecord::where('is_active', true)->where('approval_status', 'Approved')->count(),
            'pending_match_reviews' => IdentityMatchReview::where('status', 'Pending Review')->count(),
            'pending_leaves' => $pendingLeaves,
            'upcoming_holidays_count' => Holiday::withoutGlobalScopes()->where('company_id', $activeCompanyId)->where('date', '>=', now()->toDateString())->count(),
            'payroll_runs_count' => PayrollRun::where('company_id', $activeCompanyId)->count(),
            'pending_payroll_approvals' => PayrollRun::where('company_id', $activeCompanyId)->where('status', 'Pending Approval')->count(),
            'pending_fnf_count' => FnfSettlement::where('company_id', $activeCompanyId)->whereIn('status', ['Draft', 'HR Verified'])->count(),
        ];

        $recentAudits = AuditLog::with('user')
            ->where('company_id', $activeCompanyId)
            ->latest('id')
            ->take(8)
            ->get();

        return view('dashboards.admin', compact(
            'metrics',
            'recentAudits',
            'companies',
            'activeCompany',
            'companyHeadcounts',
            'deptChartLabels',
            'deptChartValues',
            'trendLabels',
            'trendJoiners',
            'kpiCards',
            'empOverviewLabels',
            'empOverviewValues',
            'attOverviewLabels',
            'attOverviewValues',
            'pendingActions',
            'recentActivity',
            'branches',
            'departments',
            'period',
            'branchId',
            'departmentId'
        ));
    }

    /**
     * Dedicated HR Executive Hub: Operational queues, eKYC, onboarding & muster checks.
     * Retains all action queues and operational workflows in a colorful, modern interface.
     */
    public function hr(): View
    {
        $activeCompany = $this->companyContext->getActiveCompany();
        $activeCompanyId = $activeCompany?->id;
        $today = now()->toDateString();

        $metrics = [
            'total_workforce' => Employee::where('company_id', $activeCompanyId)->where('status', 'Active')->count(),
            'onboarding_pipeline' => Employee::where('company_id', $activeCompanyId)->whereIn('status', ['Draft', 'Onboarding', 'Pending eKYC'])->count(),
            'ekyc_pending_review' => Employee::where('company_id', $activeCompanyId)->whereIn('ekyc_status', ['Pending Verification', 'Grace Period Active'])->count(),
            'probation_due_30d' => Employee::where('company_id', $activeCompanyId)->where('status', 'Active')->whereNotNull('probation_end_date')->where('probation_end_date', '<=', now()->addDays(30))->count(),
            'leave_approvals_pending' => LeaveApplication::where('company_id', $activeCompanyId)->where('status', 'Pending HR')->count(),
            'regularizations_pending' => AttendanceRegularization::where('company_id', $activeCompanyId)->where('status', 'Pending HR')->count(),
            'present_today' => AttendanceRecord::where('company_id', $activeCompanyId)->where('attendance_date', $today)->whereIn('status', ['Present', 'Half Day', 'Worked During Shutdown', 'On Duty'])->count(),
            'absent_today' => AttendanceRecord::where('company_id', $activeCompanyId)->where('attendance_date', $today)->where('status', 'Absent')->count(),
            'missing_punch_today' => AttendanceRecord::where('company_id', $activeCompanyId)->where('attendance_date', $today)->where('status', 'Missing Punch')->count(),
        ];

        // Action Queues
        $pendingEkycEmployees = Employee::where('company_id', $activeCompanyId)
            ->whereIn('ekyc_status', ['Pending Verification', 'Grace Period Active'])
            ->with(['department', 'designation'])
            ->limit(5)
            ->get();

        $upcomingProbations = Employee::where('company_id', $activeCompanyId)
            ->where('status', 'Active')
            ->whereNotNull('probation_end_date')
            ->where('probation_end_date', '<=', now()->addDays(30))
            ->with(['department', 'reportingManager'])
            ->orderBy('probation_end_date')
            ->limit(5)
            ->get();

        $pendingLeaves = LeaveApplication::where('company_id', $activeCompanyId)
            ->where('status', 'Pending HR')
            ->with(['employee.department', 'leaveType'])
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $pendingRegularizations = AttendanceRegularization::where('company_id', $activeCompanyId)
            ->where('status', 'Pending HR')
            ->with(['employee.department'])
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        // Department breakdown for Chart.js
        $deptData = Department::where('company_id', $activeCompanyId)
            ->withCount(['employees' => fn($q) => $q->where('status', 'Active')])
            ->orderByDesc('employees_count')
            ->limit(6)
            ->get();

        $deptChartLabels = $deptData->pluck('name')->toArray();
        $deptChartValues = $deptData->pluck('employees_count')->toArray();

        return view('dashboards.hr_executive', compact(
            'activeCompany',
            'metrics',
            'pendingEkycEmployees',
            'upcomingProbations',
            'pendingLeaves',
            'pendingRegularizations',
            'deptChartLabels',
            'deptChartValues'
        ));
    }

    /**
     * Manager MSS Portal: Strictly scoped to direct and subordinate team members.
     * Adheres strictly to Page 94 segregation rules (ZERO unauthorized salary data).
     */
    public function manager(): View
    {
        $activeCompany = $this->companyContext->getActiveCompany();
        $activeCompanyId = $activeCompany?->id;
        $user = Auth::user();
        $managerEmp = $user->employee();

        $teamMembers = $managerEmp
            ? Employee::where('company_id', $activeCompanyId)
                ->where('reporting_manager_id', $managerEmp->id)
                ->with(['department', 'designation'])
                ->get()
            : collect();

        $teamMemberIds = $teamMembers->pluck('id')->toArray();
        $teamCount = count($teamMemberIds);
        $today = now()->toDateString();

        // Compute real-time today's attendance for the manager's team
        $presentToday = 0;
        $onLeaveToday = 0;
        $absentToday = 0;

        if ($teamCount > 0) {
            $todayRecords = AttendanceRecord::whereIn('employee_id', $teamMemberIds)
                ->where('attendance_date', $today)
                ->get();

            $presentToday = $todayRecords->whereIn('status', ['Present', 'Half Day', 'Worked During Shutdown', 'On Duty'])->count();
            $onLeaveToday = $todayRecords->whereIn('status', ['Holiday', 'Weekly Off'])->count();
            $absentToday = $todayRecords->where('status', 'Absent')->count();
        }

        $pendingLeaveApprovals = $teamCount > 0
            ? LeaveApplication::whereIn('employee_id', $teamMemberIds)
                ->where('status', 'Pending Manager')
                ->count()
            : 0;

        $pendingRegularizations = $teamCount > 0
            ? AttendanceRegularization::whereIn('employee_id', $teamMemberIds)
                ->where('status', 'Pending Manager')
                ->count()
            : 0;

        $pendingOtApprovals = $teamCount > 0
            ? OvertimeRecord::whereIn('employee_id', $teamMemberIds)
                ->where('status', 'Pending')
                ->count()
            : 0;

        $metrics = [
            'team_members' => $teamCount,
            'present_today' => $presentToday,
            'on_leave' => $onLeaveToday,
            'absent_today' => $absentToday,
            'pending_approvals' => $pendingLeaveApprovals,
            'pending_regularizations' => $pendingRegularizations,
            'pending_ot' => $pendingOtApprovals,
        ];

        // Team leave approval queue
        $teamLeaveRequests = $teamCount > 0
            ? LeaveApplication::whereIn('employee_id', $teamMemberIds)
                ->where('status', 'Pending Manager')
                ->with(['employee.designation', 'leaveType'])
                ->orderByDesc('id')
                ->limit(6)
                ->get()
            : collect();

        // Team regularization queue
        $teamRegularizations = $teamCount > 0
            ? AttendanceRegularization::whereIn('employee_id', $teamMemberIds)
                ->where('status', 'Pending Manager')
                ->with(['employee'])
                ->orderByDesc('id')
                ->limit(6)
                ->get()
            : collect();

        return view('dashboards.manager', compact(
            'metrics',
            'activeCompany',
            'user',
            'teamMembers',
            'teamLeaveRequests',
            'teamRegularizations'
        ));
    }

    /**
     * Employee ESS Portal: Personal attendance, leave balances, payslips & requests.
     */
    public function employee(): View
    {
        $activeCompany = $this->companyContext->getActiveCompany();
        $activeCompanyId = $activeCompany?->id;
        $user = Auth::user();
        $emp = $user->employee();

        $availableLeaves = 0.00;
        $leavesTaken = 0.00;
        $pendingLeaves = 0;
        $attendancePercent = 100.0;
        $todayRecord = null;
        $latestPayslip = null;
        $allocatedAssets = collect();
        $myDocumentsCount = 0;

        if ($emp) {
            $year = (int) now()->format('Y');

            $balances = LeaveBalance::withoutGlobalScopes()
                ->where('company_id', $activeCompanyId)
                ->where('employee_id', $emp->id)
                ->where('year', $year)
                ->get();

            $availableLeaves = (float) $balances->sum('available_balance');
            $leavesTaken = (float) $balances->sum('consumed_balance');

            $pendingLeaves = LeaveApplication::withoutGlobalScopes()
                ->where('company_id', $activeCompanyId)
                ->where('employee_id', $emp->id)
                ->whereIn('status', ['Pending Manager', 'Pending HOD', 'Pending HR'])
                ->count();

            // Real attendance rate for the current month
            $startOfMonth = now()->startOfMonth()->toDateString();
            $monthRecords = AttendanceRecord::where('employee_id', $emp->id)
                ->whereBetween('attendance_date', [$startOfMonth, now()->toDateString()])
                ->get();

            if ($monthRecords->isNotEmpty()) {
                $pCount = $monthRecords->whereIn('status', ['Present', 'Half Day', 'Weekly Off', 'Holiday', 'On Duty'])->count();
                $attendancePercent = round(($pCount / $monthRecords->count()) * 100, 1);
            }

            // Today's attendance punch
            $todayRecord = AttendanceRecord::where('employee_id', $emp->id)
                ->where('attendance_date', now()->toDateString())
                ->first();

            // Latest Payslip
            $latestPayslip = PayrollRunEmployee::where('employee_id', $emp->id)
                ->orderByDesc('id')
                ->first();

            // Allocated Assets
            $allocatedAssets = AssetAllocation::where('employee_id', $emp->id)
                ->where('status', 'Active')
                ->with(['asset'])
                ->get();

            // Issued Documents
            $myDocumentsCount = GeneratedDocument::where('employee_id', $emp->id)
                ->whereIn('status', ['Issued', 'Generated'])
                ->count();
        }

        $metrics = [
            'available_leaves' => $availableLeaves,
            'leaves_taken' => $leavesTaken,
            'attendance_percent' => $attendancePercent,
            'pending_leaves' => $pendingLeaves,
            'allocated_assets_count' => $allocatedAssets->count(),
            'my_documents_count' => $myDocumentsCount,
        ];

        return view('dashboards.employee', compact(
            'metrics',
            'activeCompany',
            'user',
            'emp',
            'todayRecord',
            'latestPayslip',
            'allocatedAssets'
        ));
    }
}
