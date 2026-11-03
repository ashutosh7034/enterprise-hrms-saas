<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_run_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('employee_salary_assignment_id')->nullable()->constrained('employee_salary_assignments')->nullOnDelete();
            
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');

            // Attendance & Day Breakdown
            $table->decimal('calendar_days', 4, 2)->default(0.00);
            $table->decimal('worked_days', 4, 2)->default(0.00);
            $table->decimal('paid_leave_days', 4, 2)->default(0.00);
            $table->decimal('weekly_off_days', 4, 2)->default(0.00);
            $table->decimal('holiday_days', 4, 2)->default(0.00);
            $table->decimal('loss_of_pay_days', 4, 2)->default(0.00);
            $table->decimal('paid_days', 4, 2)->default(0.00);

            // Overtime
            $table->decimal('overtime_hours', 5, 2)->default(0.00);
            $table->decimal('overtime_amount', 10, 2)->default(0.00);

            // Earnings
            $table->decimal('basic_salary', 10, 2)->default(0.00);
            $table->decimal('hra', 10, 2)->default(0.00);
            $table->decimal('da', 10, 2)->default(0.00);
            $table->decimal('allowances_amount', 10, 2)->default(0.00);
            $table->decimal('gross_earnings', 10, 2)->default(0.00);

            // Statutory Deductions (Employee)
            $table->decimal('pf_employee', 10, 2)->default(0.00);
            $table->decimal('esi_employee', 10, 2)->default(0.00);
            $table->decimal('professional_tax', 10, 2)->default(0.00);
            $table->decimal('labour_welfare_fund', 10, 2)->default(0.00);
            $table->decimal('income_tax_tds', 10, 2)->default(0.00);

            // Statutory Contributions (Employer)
            $table->decimal('pf_employer', 10, 2)->default(0.00);
            $table->decimal('esi_employer', 10, 2)->default(0.00);

            // Other Deductions
            $table->decimal('loan_emi_deduction', 10, 2)->default(0.00);
            $table->decimal('fine_recovery_deduction', 10, 2)->default(0.00);
            $table->decimal('other_deductions', 10, 2)->default(0.00);
            $table->decimal('total_deductions', 10, 2)->default(0.00);

            // Net Salary
            $table->decimal('net_salary', 10, 2)->default(0.00);

            // Payslip metadata
            $table->enum('payment_status', ['Pending', 'Paid', 'On Hold'])->default('Pending');
            $table->string('payment_reference', 100)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payslip_number', 50)->nullable();
            $table->string('pdf_path', 255)->nullable();
            $table->json('calculation_snapshot_json')->nullable();

            $table->timestamps();

            $table->unique(['payroll_run_id', 'employee_id'], 'uniq_payroll_run_employee');
            $table->index(['company_id', 'employee_id', 'year', 'month'], 'idx_emp_payroll_history');
        });

        Schema::create('payroll_component_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_employee_id')->constrained('payroll_run_employees')->cascadeOnDelete();
            $table->foreignId('salary_component_id')->nullable()->constrained('salary_components')->nullOnDelete();
            $table->enum('component_type', ['Earning', 'Deduction', 'Employer Contribution']);
            $table->string('component_code', 50);
            $table->string('component_name', 100);
            $table->decimal('standard_amount', 10, 2)->default(0.00);
            $table->decimal('earned_or_deducted_amount', 10, 2)->default(0.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_component_transactions');
        Schema::dropIfExists('payroll_run_employees');
    }
};

