<?php

namespace App\Models\Traits;

use App\Services\AuditLogService;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            app(AuditLogService::class)->log(
                action: 'CREATE',
                model: $model,
                oldValues: null,
                newValues: $model->getAttributes()
            );
        });

        static::updated(function ($model) {
            $dirty = $model->getDirty();
            if (!empty($dirty)) {
                $oldValues = array_intersect_key($model->getOriginal(), $dirty);
                app(AuditLogService::class)->log(
                    action: 'UPDATE',
                    model: $model,
                    oldValues: $oldValues,
                    newValues: $dirty
                );
            }
        });

        static::deleted(function ($model) {
            app(AuditLogService::class)->log(
                action: 'DELETE',
                model: $model,
                oldValues: $model->getOriginal(),
                newValues: null
            );
        });
    }
}

