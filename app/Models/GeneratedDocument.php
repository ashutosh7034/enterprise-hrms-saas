<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GeneratedDocument extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'employee_id',
        'template_id',
        'template_version_id',
        'document_number',
        'verification_code',
        'category',
        'document_type',
        'title',
        'content_snapshot_html',
        'metadata_snapshot_json',
        'pdf_file_path',
        'file_size_kb',
        'status',
        'effective_date',
        'requested_by',
        'generated_by',
        'approved_by',
        'approved_at',
        'issued_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'metadata_snapshot_json' => 'array',
            'effective_date' => 'date',
            'approved_at' => 'datetime',
            'issued_at' => 'datetime',
            'file_size_kb' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplateVersion::class, 'template_version_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function verificationLogs(): HasMany
    {
        return $this->hasMany(DocumentVerificationLog::class, 'generated_document_id');
    }

    public function scopeIssued(Builder $query): Builder
    {
        return $query->where('status', 'Issued');
    }

    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->where('status', 'Pending Approval');
    }

    public function isIssued(): bool
    {
        return $this->status === 'Issued';
    }

    public function isPendingApproval(): bool
    {
        return $this->status === 'Pending Approval';
    }
}

