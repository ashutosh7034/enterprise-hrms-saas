<?php

namespace App\Http\Controllers;

use App\Models\LeavePeriodLock;
use App\Services\CompanyContextService;
use App\Services\LeavePeriodLockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeavePeriodLockController extends Controller
{
    public function __construct(
        protected CompanyContextService $companyContext,
        protected LeavePeriodLockService $lockService
    ) {}

    public function index(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $locks = LeavePeriodLock::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->with(['locker', 'unlockHistories.unlocker'])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(15);

        return view('leaves.period_locks.index', compact('locks'));
    }

    public function store(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2030',
            'month' => 'required|integer|min:1|max:12',
            'remarks' => 'nullable|string|max:500',
        ]);

        $this->lockService->lockPeriod(
            companyId: $companyId,
            year: (int) $validated['year'],
            month: (int) $validated['month'],
            locker: Auth::user(),
            remarks: $validated['remarks']
        );

        return back()->with('success', "Leave period {$validated['year']}-" . sprintf('%02d', $validated['month']) . " successfully locked.");
    }

    public function unlock(Request $request, LeavePeriodLock $lock)
    {
        $user = Auth::user();
        $companyId = $this->companyContext->getActiveCompanyId();

        if ($lock->company_id !== $companyId && !$user->isHoAdmin()) {
            abort(404);
        }

        $validated = $request->validate([
            'justification' => 'required|string|min:10|max:1000',
        ]);

        $result = $this->lockService->unlockPeriod(
            lock: $lock,
            hoAdmin: $user,
            justification: $validated['justification']
        );

        if ($result['status'] !== 'Success') {
            return back()->withErrors(['unlock_error' => $result['message']]);
        }

        return back()->with('success', "Leave period unlocked by HO Admin. Override justification permanently archived.");
    }

    public function relock(LeavePeriodLock $lock)
    {
        $user = Auth::user();
        $companyId = $this->companyContext->getActiveCompanyId();

        if ($lock->company_id !== $companyId && !$user->isHoAdmin()) {
            abort(404);
        }

        $this->lockService->relockPeriod($lock, $user);

        return back()->with('success', "Leave period re-locked successfully.");
    }
}

