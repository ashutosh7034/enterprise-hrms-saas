<?php

namespace App\Services;

use App\Models\OnboardingDropdownMaster;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class OnboardingMasterService
{
    public function __construct(
        protected AuditLogService $auditLog,
        protected CompanyContextService $companyContext
    ) {}

    /**
     * Retrieve all active dropdown masters grouped by category for onboarding.
     *
     * @return array<string, Collection<int, OnboardingDropdownMaster>>
     */
    public function getActiveMastersGrouped(?int $companyId = null, ?int $tenantId = null, ?int $locationId = null): array
    {
        $companyId = $companyId ?: $this->companyContext->getActiveCompanyId();
        $tenantId = $tenantId ?: $this->companyContext->getActiveTenantId();

        $allMasters = OnboardingDropdownMaster::active()
            ->forScope($companyId, $tenantId, $locationId)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        $grouped = [];
        foreach (array_keys(OnboardingDropdownMaster::CATEGORIES) as $categoryKey) {
            $grouped[$categoryKey] = $allMasters->where('category', $categoryKey)->values();
        }

        return $grouped;
    }

    /**
     * Retrieve options for a single category.
     */
    public function getOptions(string $category, ?int $companyId = null, ?int $tenantId = null, ?int $locationId = null): Collection
    {
        $companyId = $companyId ?: $this->companyContext->getActiveCompanyId();
        $tenantId = $tenantId ?: $this->companyContext->getActiveTenantId();

        return OnboardingDropdownMaster::getOptions($category, $companyId, $tenantId, $locationId);
    }

    /**
     * Retrieve dependent options for a parent master item.
     */
    public function getDependentOptions(int $parentId): Collection
    {
        return OnboardingDropdownMaster::active()
            ->where('parent_id', $parentId)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Create a new dropdown master item with audit logging.
     */
    public function create(array $data, ?int $companyId = null, ?int $tenantId = null): OnboardingDropdownMaster
    {
        $companyId = array_key_exists('company_id', $data) ? $data['company_id'] : ($companyId ?? $this->companyContext->getActiveCompanyId());
        $tenantId = array_key_exists('tenant_id', $data) ? $data['tenant_id'] : ($tenantId ?? $this->companyContext->getActiveTenantId());
        $locationId = array_key_exists('location_id', $data) ? $data['location_id'] : null;

        $master = OnboardingDropdownMaster::create([
            'tenant_id' => $tenantId,
            'company_id' => $companyId,
            'location_id' => $locationId,
            'category' => $data['category'],
            'name' => trim($data['name']),
            'code' => trim($data['code'] ?? strtoupper(preg_replace('/[^a-zA-Z0-9_]/', '_', $data['name']))),
            'description' => $data['description'] ?? null,
            'parent_id' => !empty($data['parent_id']) ? (int) $data['parent_id'] : null,
            'display_order' => (int) ($data['display_order'] ?? 0),
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
            'metadata' => $data['metadata'] ?? null,
        ]);

        $this->auditLog->log(
            action: 'CREATE',
            companyId: $companyId,
            newValues: [
                'category' => $master->category,
                'name' => $master->name,
                'code' => $master->code,
                'is_active' => $master->is_active,
                'company_id' => $master->company_id,
                'location_id' => $master->location_id,
            ],
            customAuditableType: OnboardingDropdownMaster::class,
            customAuditableId: $master->id
        );

        return $master;
    }

    /**
     * Update an existing dropdown master item with audit logging.
     */
    public function update(OnboardingDropdownMaster $master, array $data): OnboardingDropdownMaster
    {
        $oldValues = [
            'name' => $master->name,
            'code' => $master->code,
            'display_order' => $master->display_order,
            'is_active' => $master->is_active,
            'parent_id' => $master->parent_id,
            'company_id' => $master->company_id,
            'location_id' => $master->location_id,
        ];

        $updateData = [
            'name' => isset($data['name']) ? trim($data['name']) : $master->name,
            'code' => isset($data['code']) ? trim($data['code']) : $master->code,
            'description' => array_key_exists('description', $data) ? $data['description'] : $master->description,
            'parent_id' => array_key_exists('parent_id', $data) ? ($data['parent_id'] ? (int) $data['parent_id'] : null) : $master->parent_id,
            'display_order' => isset($data['display_order']) ? (int) $data['display_order'] : $master->display_order,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : $master->is_active,
            'metadata' => array_key_exists('metadata', $data) ? $data['metadata'] : $master->metadata,
        ];

        if (array_key_exists('company_id', $data)) {
            $updateData['company_id'] = $data['company_id'];
        }
        if (array_key_exists('location_id', $data)) {
            $updateData['location_id'] = $data['location_id'];
        }

        $master->update($updateData);

        $this->auditLog->log(
            action: 'UPDATE',
            companyId: $master->company_id,
            oldValues: $oldValues,
            newValues: [
                'name' => $master->name,
                'code' => $master->code,
                'display_order' => $master->display_order,
                'is_active' => $master->is_active,
                'parent_id' => $master->parent_id,
                'company_id' => $master->company_id,
                'location_id' => $master->location_id,
            ],
            customAuditableType: OnboardingDropdownMaster::class,
            customAuditableId: $master->id
        );

        return $master;
    }

    /**
     * Toggle active status with audit logging.
     */
    public function toggleStatus(OnboardingDropdownMaster $master): bool
    {
        $oldStatus = $master->is_active;
        $newStatus = !$oldStatus;

        $master->is_active = $newStatus;
        $master->save();

        $this->auditLog->log(
            action: $newStatus ? 'MASTER VALUE ACTIVATED' : 'MASTER VALUE DEACTIVATED',
            companyId: $master->company_id,
            oldValues: ['is_active' => $oldStatus],
            newValues: ['is_active' => $newStatus, 'name' => $master->name, 'category' => $master->category],
            customAuditableType: OnboardingDropdownMaster::class,
            customAuditableId: $master->id
        );

        return $newStatus;
    }
}

