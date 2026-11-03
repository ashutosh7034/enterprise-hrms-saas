<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocumentVersion extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'employee_document_id',
        'file_path',
        'original_name',
        'mime_type',
        'file_size_kb',
        'version',
        'uploaded_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'version' => 'integer',
            'file_size_kb' => 'integer',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(EmployeeDocument::class, 'employee_document_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getVersionNumberAttribute(): int
    {
        return $this->version;
    }

    public function getFileNameAttribute(): string
    {
        return $this->original_name;
    }
}
