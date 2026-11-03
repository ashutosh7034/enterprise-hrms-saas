<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('loan_application_number', 50);
            $table->enum('loan_type', ['Personal Loan', 'Salary Advance', 'Emergency Medical Advance', 'Festival Advance'])->default('Salary Advance');
            $table->decimal('principal_amount', 10, 2);
            $table->decimal('interest_rate_percent', 4, 2)->default(0.00);
            $table->unsignedSmallInteger('tenure_months')->default(1);
            $table->decimal('monthly_emi', 10, 2);
            $table->decimal('disbursed_amount', 10, 2)->default(0.00);
            $table->date('disbursed_date')->nullable();
            $table->decimal('total_recovered', 10, 2)->default(0.00);
            $table->decimal('remaining_balance', 10, 2);
            $table->text('reason')->nullable();
            $table->enum('status', ['Applied', 'Manager Approved', 'HR Approved', 'Disbursed', 'Active', 'Closed', 'Rejected'])->default('Applied');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'loan_application_number'], 'uniq_loan_app_num');
            $table->index(['company_id', 'employee_id', 'status'], 'idx_loan_emp_status');
        });

        Schema::create('employee_loan_emi_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_loan_id')->constrained('employee_loans')->cascadeOnDelete();
            $table->unsignedSmallInteger('installment_number');
            $table->unsignedTinyInteger('due_month');
            $table->unsignedSmallInteger('due_year');
            $table->decimal('emi_amount', 10, 2);
            $table->decimal('principal_component', 10, 2);
            $table->decimal('interest_component', 10, 2)->default(0.00);
            $table->enum('status', ['Pending', 'Deducted in Payroll', 'Paid via FNF', 'Waived'])->default('Pending');
            $table->unsignedBigInteger('payroll_run_id')->nullable();
            $table->timestamp('deducted_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_loan_id', 'installment_number'], 'uniq_loan_emi_num');
            $table->index(['company_id', 'due_year', 'due_month', 'status'], 'idx_loan_due_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loan_emi_schedules');
        Schema::dropIfExists('employee_loans');
    }
};

