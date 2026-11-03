<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Services\CompanyContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Designation extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'department_id',
        'title',
        'code',
        'description',
        'status',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('company', function (Builder $builder) {
            $companyContext = app(CompanyContextService::class);
            $activeCompanyId = $companyContext->getActiveCompanyId();

            if ($activeCompanyId !== null) {
                $builder->whereHas('department', function (Builder $query) use ($activeCompanyId) {
                    $query->where('company_id', $activeCompanyId);
                });
            }
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function employees(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function getNameAttribute(): string
    {
        return $this->title;
    }
}

