<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'user_id',
        'user_name',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        // Enforce immutability
        static::updating(function () {
            throw new RuntimeException('Audit records are immutable and cannot be modified.');
        });

        static::deleting(function () {
            throw new RuntimeException('Audit records are permanent and cannot be deleted.');
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Formats raw audit log state changes into human-readable details.
     */
    public function getFormattedDetailsAttribute(): string
    {
        if ($this->action === 'COMPANY_SWITCH') {
            $companyName = $this->new_values['company_name'] ?? null;
            $companyId = $this->new_values['company_id'] ?? null;
            if ($companyName) {
                return '<span class="text-secondary">Switched to:</span> <strong class="text-dark">' . e($companyName) . '</strong>' . ($companyId ? ' <span class="badge bg-light text-secondary border">#' . e($companyId) . '</span>' : '');
            }
            return '<span class="text-secondary">Company switched</span>';
        }

        if ($this->action === 'LOGIN') {
            $email = $this->new_values['email'] ?? $this->user?->email;
            if ($email) {
                return '<span class="text-secondary">Signed in:</span> <strong class="text-dark">' . e($email) . '</strong>';
            }
            return '<span class="text-secondary">User authenticated</span>';
        }

        if ($this->action === 'LOGOUT') {
            return '<span class="text-secondary">Signed out</span>';
        }

        $values = $this->new_values ?? $this->old_values;
        if (empty($values) || !is_array($values)) {
            return '<span class="text-muted fst-italic">System recorded</span>';
        }

        return $this->formatValuesArray($values);
    }

    /**
     * Convert an associative payload array into human-readable label-value pairs.
     */
    public function formatValuesArray(?array $values, int $max = 3): string
    {
        if (empty($values)) {
            return '<span class="text-muted fst-italic">None</span>';
        }

        $parts = [];
        $hiddenKeys = ['password', 'remember_token', 'created_at', 'updated_at', 'deleted_at'];
        $count = 0;

        foreach ($values as $key => $val) {
            if (in_array(strtolower($key), $hiddenKeys, true)) {
                continue;
            }

            if ($count >= $max) {
                $remaining = count($values) - $count;
                $parts[] = '<span class="badge bg-light text-secondary border" title="' . e(json_encode($values)) . '">+' . $remaining . ' more</span>';
                break;
            }

            $label = ucwords(str_replace('_', ' ', $key));
            if (is_array($val)) {
                $valStr = json_encode($val);
            } elseif (is_bool($val)) {
                $valStr = $val ? 'Yes' : 'No';
            } elseif ($val === null || $val === '') {
                $valStr = 'None';
            } else {
                $valStr = (string) $val;
            }

            $parts[] = '<span class="text-secondary">' . e($label) . ':</span> <strong class="text-dark">' . e(\Illuminate\Support\Str::limit($valStr, 30)) . '</strong>';
            $count++;
        }

        if (empty($parts)) {
            return '<span class="text-muted fst-italic">State recorded</span>';
        }

        return implode(' <span class="text-muted opacity-50">•</span> ', $parts);
    }
}

