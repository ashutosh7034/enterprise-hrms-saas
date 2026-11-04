<?php

namespace App\Models;

use App\Models\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, Auditable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
        'role_id',
        'employee_code',
        'phone',
        'is_active',
        'can_switch_company',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'can_switch_company' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function tenant(): ?Tenant
    {
        return $this->company?->tenant;
    }

    public function notifications()
    {
        return $this->morphMany(HrmsNotification::class, 'notifiable')->latest();
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole(string|array $roles): bool
    {
        if (!$this->role) {
            return false;
        }

        if (is_array($roles)) {
            return in_array($this->role->name, $roles);
        }

        return $this->role->name === $roles;
    }

    public function hasAnyRole(string|array $roles): bool
    {
        return $this->hasRole($roles);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->hasRole(['ho_admin', 'super_admin'])) {
            return true;
        }

        return $this->role ? $this->role->hasPermission($permission) : false;
    }

    public function isHoAdmin(): bool
    {
        return $this->hasRole(['ho_admin', 'super_admin']);
    }

    public function isHr(): bool
    {
        return $this->hasRole('hr_executive') || $this->isHoAdmin();
    }

    public function isHrExecutive(): bool
    {
        return $this->hasRole('hr_executive');
    }

    public function isManager(): bool
    {
        return $this->hasRole('manager_mss');
    }

    public function isEmployee(): bool
    {
        return $this->hasRole('employee_ess');
    }

    public function getEmployeeAttribute(): ?Employee
    {
        return $this->employee();
    }

    public function employee(): ?Employee
    {
        if ($this->employee_code && $this->company_id) {
            $emp = Employee::withoutGlobalScopes()
                ->where('company_id', $this->company_id)
                ->where('employee_code', $this->employee_code)
                ->first();
            if ($emp) {
                return $emp;
            }
        }

        if ($this->email) {
            $query = Employee::withoutGlobalScopes()->where('email', $this->email);
            if ($this->company_id) {
                $query->where('company_id', $this->company_id);
            }
            return $query->first();
        }

        return null;
    }
}
