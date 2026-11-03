<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetAllocation;
use App\Models\AssetDamageAssessment;
use App\Models\Employee;
use App\Models\FineMaster;
use App\Models\FineRecoveryRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AssetManagementService
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Allocate an asset to an employee.
     */
    public function allocateAsset(
        Asset $asset,
        Employee $employee,
        array $data,
        ?User $allocatedBy = null
    ): AssetAllocation {
        if ($asset->status !== 'Available') {
            throw new InvalidArgumentException("Asset {$asset->asset_code} is not available for allocation (Current status: {$asset->status}).");
        }

        if ($asset->company_id !== $employee->company_id) {
            throw new InvalidArgumentException("Cannot allocate an asset across different company boundaries.");
        }

        return DB::transaction(function () use ($asset, $employee, $data, $allocatedBy) {
            $allocation = AssetAllocation::create([
                'asset_id' => $asset->id,
                'employee_id' => $employee->id,
                'company_id' => $asset->company_id,
                'allocated_date' => $data['allocated_date'] ?? now()->toDateString(),
                'condition_at_issue' => $data['condition_at_issue'] ?? $asset->condition ?? 'Good',
                'acknowledged_by_employee' => !empty($data['acknowledged_by_employee']),
                'acknowledged_at' => !empty($data['acknowledged_by_employee']) ? now() : null,
                'allocated_by' => $allocatedBy?->id,
                'status' => 'Active',
                'remarks' => $data['remarks'] ?? null,
            ]);

            $asset->update([
                'status' => 'Allocated',
            ]);

            $this->auditLogService->log(
                action: 'Asset Allocated',
                entityType: 'AssetAllocation',
                entityId: $allocation->id,
                oldValues: ['status' => 'Available'],
                newValues: [
                    'asset_code' => $asset->asset_code,
                    'employee_code' => $employee->employee_code,
                    'condition' => $allocation->condition_at_issue,
                ],
                companyId: $asset->company_id,
                actor: $allocatedBy
            );

            return $allocation;
        });
    }

    /**
     * Process asset return and condition check.
     */
    public function returnAsset(
        AssetAllocation $allocation,
        array $data,
        ?User $receivedBy = null
    ): AssetAllocation {
        if ($allocation->status !== 'Active') {
            throw new InvalidArgumentException("This asset allocation is already closed or returned.");
        }

        return DB::transaction(function () use ($allocation, $data, $receivedBy) {
            $condition = $data['condition_at_return'] ?? 'Good';
            $allocationStatus = match ($condition) {
                'Damaged', 'Heavy Wear & Tear' => 'Damaged',
                'Lost / Stolen', 'Lost' => 'Lost',
                default => 'Returned',
            };

            $assetStatus = match ($allocationStatus) {
                'Damaged' => 'Damaged',
                'Lost' => 'Lost',
                default => 'Available',
            };

            $allocation->update([
                'return_date' => $data['return_date'] ?? now()->toDateString(),
                'condition_at_return' => $condition,
                'received_by' => $receivedBy?->id,
                'status' => $allocationStatus,
                'remarks' => $data['remarks'] ?? $allocation->remarks,
            ]);

            $allocation->asset->update([
                'condition' => $condition,
                'status' => $assetStatus,
            ]);

            $this->auditLogService->log(
                action: 'Asset Returned',
                entityType: 'AssetAllocation',
                entityId: $allocation->id,
                oldValues: ['status' => 'Active'],
                newValues: [
                    'return_condition' => $condition,
                    'allocation_status' => $allocationStatus,
                    'asset_status' => $assetStatus,
                ],
                companyId: $allocation->company_id,
                actor: $receivedBy
            );

            return $allocation;
        });
    }

    /**
     * Calculate asset depreciation using Straight-Line (SLM) or Written Down Value (WDV).
     */
    public function calculateDepreciation(
        float $purchaseCost,
        ?string $purchaseDate,
        string $strategy = 'Straight-Line',
        float $usefulLifeYears = 3.0,
        float $salvagePercent = 0.05,
        float $wdvRate = 0.40
    ): array {
        if ($purchaseCost <= 0) {
            return [
                'purchase_cost' => 0.00,
                'age_years' => 0.00,
                'depreciated_value' => 0.00,
                'accumulated_depreciation' => 0.00,
                'strategy' => $strategy,
            ];
        }

        $ageYears = 0.00;
        if (!empty($purchaseDate)) {
            $parsedDate = Carbon::parse($purchaseDate);
            $ageYears = max(0.00, round($parsedDate->diffInDays(now()) / 365.25, 2));
        }

        $salvageValue = round($purchaseCost * $salvagePercent, 2);

        if ($strategy === 'Written-Down Value') {
            // WDV Formula: Cost * (1 - rate)^years
            $wdvFactor = pow(max(0.01, 1 - $wdvRate), $ageYears);
            $depreciated = max($salvageValue, round($purchaseCost * $wdvFactor, 2));
        } elseif ($strategy === 'Fixed Assessment') {
            $depreciated = $purchaseCost;
        } else {
            // Straight-Line Method: Cost - (Cost - Salvage)/UsefulLife * Age
            $annualDepreciation = ($purchaseCost - $salvageValue) / max(1.0, $usefulLifeYears);
            $totalDepreciation = $annualDepreciation * $ageYears;
            $depreciated = max($salvageValue, round($purchaseCost - $totalDepreciation, 2));
        }

        $accumulated = max(0.00, round($purchaseCost - $depreciated, 2));

        return [
            'purchase_cost' => round($purchaseCost, 2),
            'age_years' => $ageYears,
            'depreciated_value' => round($depreciated, 2),
            'accumulated_depreciation' => $accumulated,
            'strategy' => $strategy,
        ];
    }

    /**
     * Assess damage or loss on an asset allocation.
     */
    public function assessDamage(
        AssetAllocation $allocation,
        array $data,
        User $assessedBy
    ): AssetDamageAssessment {
        return DB::transaction(function () use ($allocation, $data, $assessedBy) {
            $asset = $allocation->asset;
            $strategy = $data['depreciation_strategy'] ?? 'Straight-Line';
            $depreciation = $this->calculateDepreciation(
                (float) $asset->purchase_cost,
                $asset->purchase_date?->toDateString(),
                $strategy
            );

            $assessedAmount = (float) $data['assessed_amount'];
            $installments = max(1, (int) ($data['installment_count'] ?? 1));
            $recoveryMethod = $data['recovery_method'] ?? 'Direct Payment';

            $assessment = AssetDamageAssessment::create([
                'asset_allocation_id' => $allocation->id,
                'fine_master_id' => $data['fine_master_id'] ?? null,
                'company_id' => $allocation->company_id,
                'assessed_amount' => $assessedAmount,
                'depreciated_value' => $depreciation['depreciated_value'],
                'depreciation_strategy' => $strategy,
                'condition_evidence' => $data['condition_evidence'] ?? null,
                'investigation_notes' => $data['investigation_notes'] ?? null,
                'status' => 'Pending Approval',
                'assessed_by' => $assessedBy->id,
                'net_recovery_amount' => $assessedAmount,
                'recovery_method' => $recoveryMethod,
                'installment_count' => $installments,
            ]);

            $this->auditLogService->log(
                action: 'Damage Assessment Created',
                entityType: 'AssetDamageAssessment',
                entityId: $assessment->id,
                oldValues: [],
                newValues: [
                    'asset_code' => $asset->asset_code,
                    'assessed_amount' => $assessedAmount,
                    'depreciated_value' => $depreciation['depreciated_value'],
                    'strategy' => $strategy,
                ],
                companyId: $allocation->company_id,
                actor: $assessedBy
            );

            return $assessment;
        });
    }

    /**
     * Approve damage assessment and generate recovery installment records.
     */
    public function approveAssessment(
        AssetDamageAssessment $assessment,
        User $approver
    ): AssetDamageAssessment {
        if ($assessment->status !== 'Pending Approval') {
            throw new InvalidArgumentException("Assessment is already {$assessment->status}.");
        }

        return DB::transaction(function () use ($assessment, $approver) {
            $assessment->update([
                'status' => 'Approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            // Generate installment records in fine_recovery_records
            $netAmount = (float) $assessment->net_recovery_amount;
            $count = max(1, (int) $assessment->installment_count);
            $installmentAmount = round($netAmount / $count, 2);
            $channel = $assessment->recovery_method === 'Payroll Queue' ? 'Payroll Deduction Queue' : 'Direct Payment';
            $initialStatus = $assessment->recovery_method === 'Payroll Queue' ? 'Queued for Phase 5 Payroll' : 'Pending';

            $runningTotal = 0.00;
            for ($i = 1; $i <= $count; $i++) {
                // Adjust rounding difference on final installment
                $amount = ($i === $count) ? round($netAmount - $runningTotal, 2) : $installmentAmount;
                $runningTotal += $amount;

                FineRecoveryRecord::create([
                    'damage_assessment_id' => $assessment->id,
                    'employee_id' => $assessment->allocation->employee_id,
                    'company_id' => $assessment->company_id,
                    'installment_number' => $i,
                    'amount' => $amount,
                    'payment_channel' => $channel,
                    'status' => $initialStatus,
                    'remarks' => "Installment {$i} of {$count} for {$assessment->allocation->asset->asset_code}",
                ]);
            }

            $this->auditLogService->log(
                action: 'Damage Assessment Approved',
                entityType: 'AssetDamageAssessment',
                entityId: $assessment->id,
                oldValues: ['status' => 'Pending Approval'],
                newValues: [
                    'status' => 'Approved',
                    'approved_by' => $approver->name,
                    'installments_created' => $count,
                ],
                companyId: $assessment->company_id,
                actor: $approver
            );

            return $assessment;
        });
    }

    /**
     * Request or approve a fine waiver.
     */
    public function applyWaiver(
        AssetDamageAssessment $assessment,
        float $waivedAmount,
        string $reason,
        User $user
    ): AssetDamageAssessment {
        if (empty(trim($reason))) {
            throw new InvalidArgumentException("A valid reason is required for waiver.");
        }

        return DB::transaction(function () use ($assessment, $waivedAmount, $reason, $user) {
            $waived = min((float) $assessment->assessed_amount, max(0.0, $waivedAmount));
            $net = max(0.00, round((float) $assessment->assessed_amount - $waived, 2));

            $isFullWaiver = ($net == 0.00);

            $assessment->update([
                'waiver_requested' => true,
                'waiver_reason' => $reason,
                'waived_amount' => $waived,
                'waiver_approved_by' => $user->id,
                'net_recovery_amount' => $net,
                'status' => $isFullWaiver ? 'Waived' : $assessment->status,
            ]);

            // If recovery records already exist, update them accordingly
            if ($isFullWaiver) {
                FineRecoveryRecord::where('damage_assessment_id', $assessment->id)
                    ->where('status', '!=', 'Paid')
                    ->update([
                        'status' => 'Waived',
                        'remarks' => 'Waived in full: ' . $reason,
                    ]);
            }

            $this->auditLogService->log(
                action: 'Damage Fine Waiver Applied',
                entityType: 'AssetDamageAssessment',
                entityId: $assessment->id,
                oldValues: ['waived_amount' => 0.00],
                newValues: [
                    'waived_amount' => $waived,
                    'net_recovery' => $net,
                    'reason' => $reason,
                    'approved_by' => $user->name,
                ],
                companyId: $assessment->company_id,
                actor: $user
            );

            return $assessment;
        });
    }

    /**
     * Record a direct recovery payment for a fine recovery installment.
     */
    public function recordPayment(
        FineRecoveryRecord $record,
        float $amount,
        string $channel = 'Direct Payment',
        ?string $reference = null,
        ?string $remarks = null,
        ?User $user = null
    ): FineRecoveryRecord {
        if ($record->status === 'Paid') {
            throw new InvalidArgumentException("Installment #{$record->installment_number} is already paid.");
        }

        return DB::transaction(function () use ($record, $amount, $channel, $reference, $remarks, $user) {
            $oldStatus = $record->status;
            $record->update([
                'amount' => $amount,
                'payment_channel' => $channel,
                'status' => 'Paid',
                'paid_date' => now()->toDateString(),
                'receipt_reference' => $reference,
                'recorded_by' => $user?->id,
                'remarks' => $remarks ?? $record->remarks,
            ]);

            $this->auditLogService->log(
                action: 'Fine Payment Recorded',
                entityType: 'FineRecoveryRecord',
                entityId: $record->id,
                oldValues: ['status' => $oldStatus],
                newValues: [
                    'status' => 'Paid',
                    'amount' => $amount,
                    'reference' => $reference,
                ],
                companyId: $record->company_id,
                actor: $user
            );

            return $record;
        });
    }
}
