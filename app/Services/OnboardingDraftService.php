<?php

namespace App\Services;

use App\Exceptions\ConcurrentDraftEditException;
use App\Models\OnboardingDraft;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OnboardingDraftService
{
    /**
     * Save or update an onboarding draft with optimistic locking.
     */
    public function saveDraft(
        array $payload,
        ?string $draftId,
        int $companyId,
        ?int $tenantId,
        $user,
        ?int $expectedVersion = null
    ): OnboardingDraft {
        return DB::transaction(function () use ($payload, $draftId, $companyId, $tenantId, $user, $expectedVersion) {
            $draft = null;

            if (!empty($draftId)) {
                $draft = OnboardingDraft::withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->where('draft_id', $draftId)
                    ->lockForUpdate()
                    ->first();
            }

            if ($draft) {
                // Optimistic concurrency check
                if ($expectedVersion !== null && (int)$draft->version !== (int)$expectedVersion) {
                    throw new ConcurrentDraftEditException(
                        "Draft was updated in another browser tab or session.",
                        (int)$draft->version,
                        $draft->last_saved_at?->format('h:i A')
                    );
                }
            } else {
                $draft = new OnboardingDraft();
                $draft->draft_id = 'DRFT-' . strtoupper(Str::random(10));
                $draft->company_id = $companyId;
                $draft->tenant_id = $tenantId;
                $draft->created_by = $user?->id;
                $draft->version = 1;
                $draft->status = 'draft';
                $draft->uploaded_files = ['documents' => [], 'profile_photo' => null];
            }

            // Extract quick summary fields
            $firstName = $payload['first_name'] ?? $draft->first_name;
            $middleName = $payload['middle_name'] ?? $draft->middle_name;
            $lastName = $payload['last_name'] ?? $draft->last_name;

            $candidateParts = array_filter([$firstName, $middleName, $lastName]);
            $candidateName = !empty($candidateParts) ? implode(' ', $candidateParts) : ($payload['display_name'] ?? $draft->candidate_name);

            $draft->first_name = $firstName;
            $draft->middle_name = $middleName;
            $draft->last_name = $lastName;
            $draft->candidate_name = $candidateName;
            $draft->email = $payload['email'] ?? $payload['personal_email'] ?? $draft->email;
            $draft->phone = $payload['phone'] ?? $payload['personal_phone'] ?? $draft->phone;
            $draft->location_id = !empty($payload['location_id']) ? (int)$payload['location_id'] : $draft->location_id;
            $draft->department_id = !empty($payload['department_id']) ? (int)$payload['department_id'] : $draft->department_id;
            
            if (isset($payload['current_step'])) {
                $draft->current_step = max(1, min(16, (int)$payload['current_step']));
            }

            // Merge form data safely
            $existingData = $draft->form_data ?? [];
            // Filter out system and file keys from form_data json
            $cleanPayload = collect($payload)->except([
                '_token', 'action', 'profile_photo', 'documents', 'draft_id', 'draft_version'
            ])->toArray();
            $draft->form_data = array_merge($existingData, $cleanPayload);

            // Handle Profile Photo upload if present
            if (isset($payload['profile_photo']) && $payload['profile_photo'] instanceof UploadedFile) {
                $photoPath = $payload['profile_photo']->store("onboarding_drafts/{$companyId}/{$draft->draft_id}", 'public');
                $uploadedFiles = $draft->uploaded_files ?? ['documents' => []];
                $uploadedFiles['profile_photo'] = [
                    'path' => $photoPath,
                    'url' => Storage::url($photoPath),
                    'original_name' => $payload['profile_photo']->getClientOriginalName(),
                    'uploaded_at' => now()->toIso8601String(),
                ];
                $draft->uploaded_files = $uploadedFiles;
            }

            // Recalculate progress and increment version
            $draft->completion_percentage = $draft->recalculateProgress();
            $draft->version = ($draft->version ?? 0) + 1;
            $draft->updated_by = $user?->id;
            $draft->last_saved_at = now();
            $draft->save();

            Log::info("Onboarding draft saved", [
                'draft_id' => $draft->draft_id,
                'company_id' => $companyId,
                'version' => $draft->version,
                'step' => $draft->current_step,
            ]);

            return $draft;
        });
    }

    /**
     * Upload an individual staged document directly to the draft.
     */
    public function uploadStagedDocument(
        string $draftId,
        UploadedFile $file,
        array $metadata,
        int $companyId,
        $user
    ): array {
        return DB::transaction(function () use ($draftId, $file, $metadata, $companyId, $user) {
            $draft = OnboardingDraft::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('draft_id', $draftId)
                ->lockForUpdate()
                ->firstOrFail();

            $path = $file->store("onboarding_drafts/{$companyId}/{$draft->draft_id}/documents", 'public');

            $docItem = [
                'id' => (string)Str::uuid(),
                'type' => $metadata['type'] ?? 'General',
                'category' => $metadata['category'] ?? 'Identity',
                'title' => $metadata['title'] ?? $file->getClientOriginalName(),
                'expiry' => $metadata['expiry'] ?? null,
                'path' => $path,
                'url' => Storage::url($path),
                'fileName' => $file->getClientOriginalName(),
                'fileSize' => $file->getSize(),
                'isImage' => str_starts_with($file->getMimeType(), 'image/'),
                'isPdf' => $file->getMimeType() === 'application/pdf',
                'uploaded_at' => now()->toIso8601String(),
            ];

            $uploadedFiles = $draft->uploaded_files ?? ['documents' => []];
            if (!isset($uploadedFiles['documents']) || !is_array($uploadedFiles['documents'])) {
                $uploadedFiles['documents'] = [];
            }
            $uploadedFiles['documents'][] = $docItem;
            $draft->uploaded_files = $uploadedFiles;

            $draft->completion_percentage = $draft->recalculateProgress();
            $draft->version = ($draft->version ?? 0) + 1;
            $draft->updated_by = $user?->id;
            $draft->last_saved_at = now();
            $draft->save();

            return [
                'doc' => $docItem,
                'version' => $draft->version,
                'last_saved_at' => $draft->last_saved_at->format('h:i A'),
                'completion_percentage' => $draft->completion_percentage,
            ];
        });
    }

    /**
     * Remove a staged document from the draft.
     */
    public function removeStagedDocument(string $draftId, string $docUniqueId, int $companyId, $user): bool
    {
        return DB::transaction(function () use ($draftId, $docUniqueId, $companyId, $user) {
            $draft = OnboardingDraft::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('draft_id', $draftId)
                ->lockForUpdate()
                ->first();

            if (!$draft) return false;

            $uploadedFiles = $draft->uploaded_files ?? ['documents' => []];
            $docs = $uploadedFiles['documents'] ?? [];

            $filtered = [];
            foreach ($docs as $doc) {
                if (($doc['id'] ?? '') === $docUniqueId) {
                    if (!empty($doc['path']) && Storage::disk('public')->exists($doc['path'])) {
                        Storage::disk('public')->delete($doc['path']);
                    }
                } else {
                    $filtered[] = $doc;
                }
            }

            $uploadedFiles['documents'] = $filtered;
            $draft->uploaded_files = $uploadedFiles;
            $draft->version = ($draft->version ?? 0) + 1;
            $draft->updated_by = $user?->id;
            $draft->last_saved_at = now();
            $draft->save();

            return true;
        });
    }

    /**
     * Delete draft with authorization check.
     */
    public function deleteDraft(string $draftId, int $companyId, $user): bool
    {
        $draft = OnboardingDraft::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('draft_id', $draftId)
            ->firstOrFail();

        // Delete draft directory
        Storage::disk('public')->deleteDirectory("onboarding_drafts/{$companyId}/{$draft->draft_id}");

        return (bool)$draft->delete();
    }
}

