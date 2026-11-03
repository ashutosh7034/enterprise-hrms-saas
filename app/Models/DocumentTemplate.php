<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Services\CompanyContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentTemplate extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'company_id',
        'category',
        'document_type',
        'code',
        'name',
        'description',
        'content_html',
        'version',
        'status',
        'effective_from',
        'effective_to',
        'requires_approval',
        'is_sensitive_salary',
        'header_footer_enabled',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'requires_approval' => 'boolean',
            'is_sensitive_salary' => 'boolean',
            'header_footer_enabled' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('company_or_global', function (Builder $builder) {
            $companyContext = app(CompanyContextService::class);
            $activeCompanyId = $companyContext->getActiveCompanyId();

            if ($activeCompanyId !== null) {
                $builder->where(function (Builder $query) use ($activeCompanyId) {
                    $query->where('company_id', $activeCompanyId)
                          ->orWhereNull('company_id');
                });
            }
        });

        static::creating(function (DocumentTemplate $model) {
            if ($model->company_id === null) {
                $companyContext = app(CompanyContextService::class);
                $activeCompanyId = $companyContext->getActiveCompanyId();
                if ($activeCompanyId && !isset($model->attributes['company_id'])) {
                    $model->company_id = $activeCompanyId;
                }
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentTemplateVersion::class)->orderByDesc('version_number');
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class, 'template_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'Active');
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }
}

