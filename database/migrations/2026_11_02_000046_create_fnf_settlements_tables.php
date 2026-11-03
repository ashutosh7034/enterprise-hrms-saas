<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fnf_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('settlement_number', 50);
            
            // Exit dates
            $table->date('resignation_date');
            $table->date('last_working_date');
            $table->unsignedSmallInteger('notice_period_required_days')->default(0);
            $table->unsignedSmallInteger('notice_period_served_days')->default(0);
            $table->unsignedSmallInteger('notice_shortfall_days')->default(0);

            // Payable Earnings Components
            $table->decimal('unpaid_salary_days', 4, 2)->default(0.00);
            $table->decimal('unpaid_salary_amount', 10, 2)->default(0.00);
            $table->decimal('leave_encashment_days', 4, 2)->default(0.00);
            $table->decimal('leave_encashment_amount', 10, 2)->default(0.00);
            $table->decimal('gratuity_amount', 10, 2)->default(0.00);

            // Deductions & Recoveries Components
            $table->decimal('notice_shortfall_deduction', 10, 2)->default(0.00);
            $table->decimal('loan_outstanding_deduction', 10, 2)->default(0.00);
            $table->decimal('asset_damage_fine_deduction', 10, 2)->default(0.00);
            $table->decimal('other_deductions_amount', 10, 2)->default(0.00);

            // Grand Totals
            $table->decimal('gross_settlement_amount', 12, 2)->default(0.00);
            $table->decimal('total_deductions_amount', 12, 2)->default(0.00);
            $table->decimal('net_settlement_amount', 12, 2)->default(0.00);
            $table->enum('settlement_type', ['Payable to Employee', 'Recoverable from Employee'])->default('Payable to Employee');

            // Workflow status
            $table->enum('status', ['Draft', 'HR Verified', 'Finance Approved', 'Settled', 'Cancelled'])->default('Draft');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('settled_at')->nullable();
            $table->string('statement_pdf_path', 255)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'settlement_number'], 'uniq_fnf_settle_num');
            $table->index(['company_id', 'employee_id', 'status'], 'idx_fnf_emp_status');
        });

        Schema::create('fnf_settlement_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fnf_settlement_id')->constrained('fnf_settlements')->cascadeOnDelete();
            $table->enum('component_category', ['Earning', 'Deduction']);
            $table->string('component_name', 100);
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->string('calculation_notes', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fnf_settlement_components');
        Schema::dropIfExists('fnf_settlements');
    }
};

