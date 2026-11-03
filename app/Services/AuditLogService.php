<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    public function log(
        string $action,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $companyId = null,
        ?string $customAuditableType = null,
        ?int $customAuditableId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?User $actor = null
    ): AuditLog {
        $user = $actor ?? Auth::user();
        $activeCompanyId = $companyId ?? app(CompanyContextService::class)->getActiveCompanyId();

        if (!$activeCompanyId && $user) {
            $activeCompanyId = $user->company_id;
        }

        // Clean sensitive fields from values
        $sensitiveKeys = ['password', 'remember_token', 'token'];
        if ($oldValues) {
            $oldValues = array_diff_key($oldValues, array_flip($sensitiveKeys));
        }
        if ($newValues) {
            $newValues = array_diff_key($newValues, array_flip($sensitiveKeys));
        }

        $resolvedAuditableType = $model ? get_class($model) : ($entityType ?? $customAuditableType ?? 'System');
        $resolvedAuditableId = $model ? $model->getKey() : ($entityId ?? $customAuditableId);

        return AuditLog::create([
            'company_id' => $activeCompanyId,
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'action' => substr(strtoupper($action), 0, 50),
            'auditable_type' => $resolvedAuditableType,
            'auditable_id' => $resolvedAuditableId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'user_agent' => Request::userAgent() ?? 'CLI/Testing',
        ]);
    }
}
