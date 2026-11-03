<?php

namespace App\Models\Traits;

use App\Models\Company;
use App\Services\CompanyContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            $companyContext = app(CompanyContextService::class);
            $activeCompanyId = $companyContext->getActiveCompanyId();

            if ($activeCompanyId !== null) {
                $builder->where($builder->getModel()->getTable() . '.company_id', $activeCompanyId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->company_id)) {
                $companyContext = app(CompanyContextService::class);
                $activeCompanyId = $companyContext->getActiveCompanyId();
                if ($activeCompanyId) {
                    $model->company_id = $activeCompanyId;
                }
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}

