<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeDocument extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'employee_id',
        'document_type',
        'file_path',
        'original_name',
        'mime_type',
        'file_size_kb',
        'is_mandatory',
        'verification_status',
        'rejection_reason',
        'verified_by',
        'verified_at',
        'version',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'is_mandatory' => 'boolean',
            'verified_at' => 'datetime',
            'version' => 'integer',
            'file_size_kb' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getFileNameAttribute(): string
    {
        return $this->original_name;
    }

    public function getCurrentVersionAttribute(): int
    {
        return $this->version;
    }

    public function getFileSizeAttribute(): int
    {
        return $this->file_size_kb * 1024;
    }

    public function getCompanyIdAttribute(): int
    {
        return $this->employee->company_id;
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(EmployeeDocumentVersion::class);
    }
}
