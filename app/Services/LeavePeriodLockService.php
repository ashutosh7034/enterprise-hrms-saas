<?php

namespace App\Services;

use App\Models\LeavePeriodLock;
use App\Models\LeavePeriodUnlockHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeavePeriodLockService
{
    /**
     * Check if a specific date or period is locked for a company.
     */
    public function isPeriodLocked(int $companyId, Carbon|string $date): bool
    {
        $d = is_string($date) ? Carbon::parse($date) : $date;
        $year = (int) $d->format('Y');
        $month = (int) $d->format('m');

        return LeavePeriodLock::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('year', $year)
            ->where('month', $month)
            ->where('is_locked', true)
            ->exists();
    }

    /**
     * Lock a leave period (typically by HR at month closure).
     */
    public function lockPeriod(int $companyId, int $year, int $month, User $locker, ?string $remarks = null): LeavePeriodLock
    {
        return DB::transaction(function () use ($companyId, $year, $month, $locker, $remarks) {
            $lock = LeavePeriodLock::firstOrNew([
                'company_id' => $companyId,
                'year' => $year,
                'month' => $month,
            ]);

            $lock->is_locked = true;
            $lock->locked_by = $locker->id;
            $lock->locked_at = now();
            $lock->remarks = $remarks ?? 'Period closed for payroll reconciliation';
            $lock->save();

            return $lock;
        });
    }

    /**
     * Unlock a locked period (HO Admin only, with mandatory justification).
     */
    public function unlockPeriod(
        LeavePeriodLock $lock,
        User $hoAdmin,
        string $justification
    ): array {
        if (!$hoAdmin->isHoAdmin()) {
            return ['status' => 'Unauthorized', 'message' => 'Only HO Admin is authorized to unlock closed leave periods.'];
        }

        if (empty(trim($justification))) {
            return ['status' => 'Validation Error', 'message' => 'Mandatory justification text must be provided to unlock a closed leave period.'];
        }

        return DB::transaction(function () use ($lock, $hoAdmin, $justification) {
            $lock->is_locked = false;
            $lock->save();

            LeavePeriodUnlockHistory::create([
                'leave_period_lock_id' => $lock->id,
                'unlocked_by' => $hoAdmin->id,
                'justification' => $justification,
                'unlocked_at' => now(),
            ]);

            return ['status' => 'Success', 'lock' => $lock];
        });
    }

    /**
     * Re-lock period after corrections.
     */
    public function relockPeriod(LeavePeriodLock $lock, User $actor): LeavePeriodLock
    {
        return DB::transaction(function () use ($lock, $actor) {
            $lock->is_locked = true;
            $lock->save();

            $history = $lock->unlockHistories()->whereNull('re_locked_at')->first();
            if ($history) {
                $history->update([
                    're_locked_at' => now(),
                    're_locked_by' => $actor->id,
                ]);
            }

            return $lock;
        });
    }
}

