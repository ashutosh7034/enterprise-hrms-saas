<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportExportHistory extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'user_id',
        'report_type',
        'file_format',
        'file_name',
        'file_path',
        'row_count',
        'file_size_kb',
        'filters_applied_json',
        'status',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'filters_applied_json' => 'array',
            'row_count' => 'integer',
            'file_size_kb' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

