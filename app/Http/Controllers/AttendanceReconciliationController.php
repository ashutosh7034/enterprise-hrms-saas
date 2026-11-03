<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Services\AttendanceReconciliationService;
use App\Services\CompanyContextService;
use Illuminate\Http\Request;

class AttendanceReconciliationController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected AttendanceReconciliationService $reconciliationService
    ) {}

    public function index(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $date = $request->input('date', now()->toDateString());

        $summary = $this->reconciliationService->getReconciliationSummary($companyId, $date);

        // Fetch unreconciled exception records for date
        $exceptionRecords = AttendanceRecord::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('attendance_date', $date)
            ->whereIn('status', ['Missing Punch', 'Absent', 'Site Shutdown'])
            ->with(['employee.department', 'employee.designation'])
            ->paginate(15);

        return view('attendance.reconciliation', compact('summary', 'date', 'exceptionRecords'));
    }

    public function reconcile(Request $request, AttendanceRecord $attendance)
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        if ($attendance->company_id !== $companyId) {
            abort(404);
        }

        $validated = $request->validate([
            'notes' => 'required|string|max:255',
        ]);

        $this->reconciliationService->reconcileRecord($attendance, $validated['notes']);

        return back()->with('success', 'Attendance exception record marked as reconciled.');
    }
}

