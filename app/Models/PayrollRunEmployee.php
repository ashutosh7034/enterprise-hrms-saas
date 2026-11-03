<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRunEmployee extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'payroll_run_id',
        'employee_id',
        'employee_salary_assignment_id',
        'year',
        'month',
        'calendar_days',
        'worked_days',
        'paid_leave_days',
        'weekly_off_days',
        'holiday_days',
        'loss_of_pay_days',
        'paid_days',
        'overtime_hours',
        'overtime_amount',
        'basic_salary',
        'hra',
        'da',
        'allowances_amount',
        'gross_earnings',
        'pf_employee',
        'esi_employee',
        'professional_tax',
        'labour_welfare_fund',
        'income_tax_tds',
        'pf_employer',
        'esi_employer',
        'loan_emi_deduction',
        'fine_recovery_deduction',
        'other_deductions',
        'total_deductions',
        'net_salary',
        'payment_status',
        'payment_reference',
        'paid_at',
        'payslip_number',
        'pdf_path',
        'calculation_snapshot_json',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'calendar_days' => 'decimal:2',
        'worked_days' => 'decimal:2',
        'paid_leave_days' => 'decimal:2',
        'weekly_off_days' => 'decimal:2',
        'holiday_days' => 'decimal:2',
        'loss_of_pay_days' => 'decimal:2',
        'paid_days' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'basic_salary' => 'decimal:2',
        'hra' => 'decimal:2',
        'da' => 'decimal:2',
        'allowances_amount' => 'decimal:2',
        'gross_earnings' => 'decimal:2',
        'pf_employee' => 'decimal:2',
        'esi_employee' => 'decimal:2',
        'professional_tax' => 'decimal:2',
        'labour_welfare_fund' => 'decimal:2',
        'income_tax_tds' => 'decimal:2',
        'pf_employer' => 'decimal:2',
        'esi_employer' => 'decimal:2',
        'loan_emi_deduction' => 'decimal:2',
        'fine_recovery_deduction' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'paid_at' => 'datetime',
        'calculation_snapshot_json' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function salaryAssignment(): BelongsTo
    {
        return $this->belongsTo(EmployeeSalaryAssignment::class, 'employee_salary_assignment_id');
    }

    public function componentTransactions(): HasMany
    {
        return $this->hasMany(PayrollComponentTransaction::class);
    }

    public function getFormattedPeriodAttribute(): string
    {
        $dt = \Carbon\Carbon::createFromDate($this->year, $this->month, 1);
        return $dt->format('F Y');
    }
}

