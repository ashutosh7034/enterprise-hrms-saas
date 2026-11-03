<?php

namespace App\Services;

use App\Models\CentralBlacklistRecord;
use App\Models\Employee;
use App\Models\IdentityMatchReview;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BlacklistMatchingService
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Check if a candidate matches any approved, active record in the Central Blacklist.
     * Searches globally across all legal entities.
     */
    public function checkIdentity(array $candidateData): array
    {
        $matches = [];
        $pan = !empty($candidateData['pan_number']) ? strtoupper(trim($candidateData['pan_number'])) : null;
        $phone = !empty($candidateData['phone']) ? preg_replace('/[^0-9]/', '', $candidateData['phone']) : null;
        
        $aadhaarHash = null;
        if (!empty($candidateData['aadhaar_hash'])) {
            $aadhaarHash = $candidateData['aadhaar_hash'];
        } elseif (!empty($candidateData['aadhaar_number'])) {
            $rawAadhaar = preg_replace('/[^0-9]/', '', $candidateData['aadhaar_number']);
            if (strlen($rawAadhaar) === 12) {
                $aadhaarHash = hash('sha256', $rawAadhaar);
            }
        }

        $candidateName = trim(($candidateData['first_name'] ?? '') . ' ' . ($candidateData['last_name'] ?? ''));
        if (empty($candidateName) && !empty($candidateData['name'])) {
            $candidateName = trim($candidateData['name']);
        }
        $candidateDob = !empty($candidateData['date_of_birth']) ? $candidateData['date_of_birth'] : null;

        // Query active, approved blacklist entries
        $activeRecords = CentralBlacklistRecord::where('is_active', true)
            ->where('approval_status', 'Approved')
            ->get();

        foreach ($activeRecords as $record) {
            // 1. Exact PAN Match
            if ($pan && !empty($record->pan_number) && strtoupper($record->pan_number) === $pan) {
                $matches[] = [
                    'record' => $record,
                    'type' => 'Confirmed Match',
                    'score' => 100,
                    'field' => 'PAN Number',
                    'reason' => "Exact PAN match: {$pan}",
                ];
                continue;
            }

            // 2. Exact Aadhaar Hash Match
            if ($aadhaarHash && !empty($record->aadhaar_hash) && $record->aadhaar_hash === $aadhaarHash) {
                $matches[] = [
                    'record' => $record,
                    'type' => 'Confirmed Match',
                    'score' => 100,
                    'field' => 'Aadhaar Token',
                    'reason' => "Exact tokenized Aadhaar match ({$record->aadhaar_masked})",
                ];
                continue;
            }

            // 3. Exact Phone Match
            if ($phone && !empty($record->phone)) {
                $cleanRecordPhone = preg_replace('/[^0-9]/', '', $record->phone);
                if ($cleanRecordPhone === $phone && strlen($phone) >= 10) {
                    $matches[] = [
                        'record' => $record,
                        'type' => 'Confirmed Match',
                        'score' => 95,
                        'field' => 'Phone',
                        'reason' => "Exact phone match: {$phone}",
                    ];
                    continue;
                }
            }

            // 4. Fuzzy Match: Name Similarity + Exact or Near Date of Birth
            if (!empty($candidateName) && !empty($record->name)) {
                $simScore = $this->calculateNameSimilarity($candidateName, $record->name);
                $dobMatches = false;

                if ($candidateDob && !empty($record->date_of_birth)) {
                    $candidateDobStr = is_string($candidateDob) ? substr($candidateDob, 0, 10) : $candidateDob->format('Y-m-d');
                    $recordDobStr = is_string($record->date_of_birth) ? substr($record->date_of_birth, 0, 10) : $record->date_of_birth->format('Y-m-d');
                    if ($candidateDobStr === $recordDobStr) {
                        $dobMatches = true;
                    }
                }

                if ($simScore >= 85 && $dobMatches) {
                    $matches[] = [
                        'record' => $record,
                        'type' => 'Confirmed Match',
                        'score' => 90,
                        'field' => 'Name & DOB',
                        'reason' => "High name similarity ({$simScore}%) with identical Date of Birth",
                    ];
                } elseif ($simScore >= 75) {
                    $matches[] = [
                        'record' => $record,
                        'type' => 'Potential Match',
                        'score' => (int) round($simScore),
                        'field' => 'Name',
                        'reason' => "Name similarity ({$simScore}%) detected across records" . ($dobMatches ? " with identical DOB" : ""),
                    ];
                }
            }
        }

        // Sort matches descending by score
        usort($matches, fn($a, $b) => $b['score'] <=> $a['score']);

        $highestScore = !empty($matches) ? $matches[0]['score'] : 0;
        $hasConfirmedMatch = !empty(array_filter($matches, fn($m) => $m['type'] === 'Confirmed Match'));
        $hasPotentialMatch = !empty(array_filter($matches, fn($m) => $m['type'] === 'Potential Match'));

        return [
            'is_blocked' => $hasConfirmedMatch,
            'match_found' => !empty($matches),
            'match_type' => $hasConfirmedMatch ? 'Confirmed Match' : ($hasPotentialMatch ? 'Potential Match' : null),
            'highest_score' => $highestScore,
            'matches' => $matches,
        ];
    }

    /**
     * Calculate name similarity percentage (0-100) using normalized Levenshtein.
     */
    protected function calculateNameSimilarity(string $name1, string $name2): float
    {
        $n1 = strtolower(trim(preg_replace('/\s+/', ' ', $name1)));
        $n2 = strtolower(trim(preg_replace('/\s+/', ' ', $name2)));

        if ($n1 === $n2) {
            return 100.0;
        }

        $maxLen = max(strlen($n1), strlen($n2));
        if ($maxLen === 0) {
            return 0.0;
        }

        $lev = levenshtein($n1, $n2);
        $similarity = (1 - ($lev / $maxLen)) * 100;

        return max(0.0, round($similarity, 2));
    }

    /**
     * Create an IdentityMatchReview entry when a potential or confirmed match is detected.
     */
    public function logMatchReview(
        ?int $employeeId,
        int $blacklistRecordId,
        string $matchType,
        int $matchScore,
        array $details
    ): IdentityMatchReview {
        return IdentityMatchReview::create([
            'employee_id' => $employeeId,
            'blacklist_record_id' => $blacklistRecordId,
            'match_type' => $matchType,
            'match_score' => $matchScore,
            'match_details' => $details,
            'status' => 'Pending Review',
        ]);
    }

    /**
     * Submit a candidate or former employee to the Central Blacklist (Pending Review).
     */
    public function submitRequest(array $data, User $requestedBy): CentralBlacklistRecord
    {
        return DB::transaction(function () use ($data, $requestedBy) {
            $aadhaarHash = null;
            $aadhaarMasked = null;

            if (!empty($data['aadhaar_number'])) {
                $clean = preg_replace('/[^0-9]/', '', $data['aadhaar_number']);
                if (strlen($clean) === 12) {
                    $aadhaarHash = hash('sha256', $clean);
                    $aadhaarMasked = 'XXXX-XXXX-' . substr($clean, -4);
                }
            }

            $record = CentralBlacklistRecord::create([
                'name' => trim($data['name']),
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'pan_number' => !empty($data['pan_number']) ? strtoupper(trim($data['pan_number'])) : null,
                'aadhaar_hash' => $aadhaarHash,
                'aadhaar_masked' => $aadhaarMasked,
                'phone' => !empty($data['phone']) ? preg_replace('/[^0-9]/', '', $data['phone']) : null,
                'previous_employee_code' => $data['previous_employee_code'] ?? null,
                'previous_company_id' => $data['previous_company_id'] ?? null,
                'reason_category' => $data['reason_category'],
                'evidence_notes' => $data['evidence_notes'],
                'supporting_document_path' => $data['supporting_document_path'] ?? null,
                'approval_status' => 'Pending Review',
                'requested_by' => $requestedBy->id,
                'is_active' => true,
            ]);

            $this->auditLogService->log(
                action: 'Central Blacklist Request Submitted',
                entityType: 'CentralBlacklistRecord',
                entityId: $record->id,
                oldValues: [],
                newValues: [
                    'name' => $record->name,
                    'reason_category' => $record->reason_category,
                    'pan' => $record->pan_number,
                    'aadhaar_masked' => $record->aadhaar_masked,
                ],
                companyId: $data['previous_company_id'] ?? null,
                actor: $requestedBy
            );

            return $record;
        });
    }

    /**
     * HO Admin approves the Blacklist request.
     */
    public function approveRequest(CentralBlacklistRecord $record, User $approvedBy): CentralBlacklistRecord
    {
        if (!$approvedBy->isHoAdmin()) {
            throw new InvalidArgumentException("Unauthorized: Only HO Admin can approve blacklist records.");
        }

        return DB::transaction(function () use ($record, $approvedBy) {
            $record->update([
                'approval_status' => 'Approved',
                'approved_by' => $approvedBy->id,
                'approved_at' => now(),
                'is_active' => true,
            ]);

            $this->auditLogService->log(
                action: 'Central Blacklist Request Approved',
                entityType: 'CentralBlacklistRecord',
                entityId: $record->id,
                oldValues: ['approval_status' => 'Pending Review'],
                newValues: ['approval_status' => 'Approved', 'approved_by' => $approvedBy->name],
                companyId: $record->previous_company_id,
                actor: $approvedBy
            );

            return $record;
        });
    }

    /**
     * HO Admin rejects the Blacklist request.
     */
    public function rejectRequest(CentralBlacklistRecord $record, User $reviewedBy, string $reason): CentralBlacklistRecord
    {
        if (!$reviewedBy->isHoAdmin()) {
            throw new InvalidArgumentException("Unauthorized: Only HO Admin can reject blacklist requests.");
        }

        if (empty(trim($reason))) {
            throw new InvalidArgumentException("A rejection reason is mandatory.");
        }

        return DB::transaction(function () use ($record, $reviewedBy, $reason) {
            $record->update([
                'approval_status' => 'Rejected',
                'approved_by' => $reviewedBy->id,
                'rejection_reason' => $reason,
                'is_active' => false,
            ]);

            $this->auditLogService->log(
                action: 'Central Blacklist Request Rejected',
                entityType: 'CentralBlacklistRecord',
                entityId: $record->id,
                oldValues: ['approval_status' => 'Pending Review'],
                newValues: ['approval_status' => 'Rejected', 'reason' => $reason],
                companyId: $record->previous_company_id,
                actor: $reviewedBy
            );

            return $record;
        });
    }

    /**
     * Whitelist an existing active blacklist entry with mandatory justification.
     * Record is never deleted, ensuring complete audit retention.
     */
    public function whitelistRecord(CentralBlacklistRecord $record, User $user, string $justification): CentralBlacklistRecord
    {
        if (!$user->isHoAdmin()) {
            throw new InvalidArgumentException("Unauthorized: Only HO Admin can whitelist a blacklisted individual.");
        }

        if (empty(trim($justification))) {
            throw new InvalidArgumentException("A mandatory justification is required to whitelist an individual.");
        }

        return DB::transaction(function () use ($record, $user, $justification) {
            $record->update([
                'is_active' => false,
                'whitelisted_at' => now(),
                'whitelisted_by' => $user->id,
                'whitelist_justification' => $justification,
            ]);

            $this->auditLogService->log(
                action: 'Blacklist Record Whitelisted / Cleared',
                entityType: 'CentralBlacklistRecord',
                entityId: $record->id,
                oldValues: ['is_active' => true],
                newValues: [
                    'is_active' => false,
                    'whitelisted_by' => $user->name,
                    'justification' => $justification,
                ],
                companyId: $record->previous_company_id,
                actor: $user
            );

            return $record;
        });
    }

    /**
     * Resolve an identity match review queue item.
     */
    public function resolveReview(
        IdentityMatchReview $review,
        User $reviewer,
        string $resolution,
        string $remarks
    ): IdentityMatchReview {
        if (!in_array($resolution, ['Confirmed Match', 'False Positive', 'Requires More Information'])) {
            throw new InvalidArgumentException("Invalid resolution status: {$resolution}");
        }

        return DB::transaction(function () use ($review, $reviewer, $resolution, $remarks) {
            $oldStatus = $review->status;
            $review->update([
                'status' => $resolution,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'resolution_remarks' => $remarks,
            ]);

            // If confirmed match and employee attached, update employee status
            if ($resolution === 'Confirmed Match' && $review->employee) {
                $review->employee->update([
                    'status' => 'Terminated',
                ]);
            }

            $this->auditLogService->log(
                action: 'Identity Match Review Resolved',
                entityType: 'IdentityMatchReview',
                entityId: $review->id,
                oldValues: ['status' => $oldStatus],
                newValues: [
                    'status' => $resolution,
                    'remarks' => $remarks,
                    'reviewer' => $reviewer->name,
                ],
                companyId: $review->employee?->company_id,
                actor: $reviewer
            );

            return $review;
        });
    }
}
