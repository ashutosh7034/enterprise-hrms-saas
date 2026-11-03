<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_tax_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('financial_year', 10); // e.g. 2026-2027
            $table->enum('tax_regime', ['New Regime', 'Old Regime'])->default('New Regime');
            
            // Chapter VI-A & Old Regime Deductions
            $table->decimal('sec_80c_amount', 10, 2)->default(0.00);
            $table->decimal('sec_80d_amount', 10, 2)->default(0.00);
            $table->decimal('sec_24_home_loan_interest', 10, 2)->default(0.00);
            $table->decimal('other_exemptions_amount', 10, 2)->default(0.00);
            $table->decimal('total_declared_deductions', 10, 2)->default(0.00);

            $table->enum('status', ['Draft', 'Submitted', 'Verified'])->default('Draft');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'employee_id', 'financial_year'], 'uniq_emp_tax_decl_fy');
        });

        Schema::create('employee_tax_projections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('financial_year', 10);
            $table->decimal('projected_annual_gross', 12, 2)->default(0.00);
            $table->decimal('standard_deduction', 10, 2)->default(0.00);
            $table->decimal('net_taxable_income', 12, 2)->default(0.00);
            $table->decimal('projected_annual_tax', 10, 2)->default(0.00);
            $table->decimal('cess_amount', 10, 2)->default(0.00);
            $table->decimal('net_annual_tax_liability', 10, 2)->default(0.00);
            $table->decimal('cumulative_tds_deducted', 10, 2)->default(0.00);
            $table->unsignedTinyInteger('remaining_months')->default(12);
            $table->decimal('monthly_tds_calculated', 10, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['company_id', 'employee_id', 'financial_year'], 'uniq_emp_tax_proj_fy');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_tax_projections');
        Schema::dropIfExists('employee_tax_declarations');
    }
};

