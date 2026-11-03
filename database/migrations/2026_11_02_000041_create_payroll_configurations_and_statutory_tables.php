<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->date('effective_from_date');
            $table->enum('attendance_calculation_basis', ['Actual Calendar Days', 'Standard 30 Days', 'Standard 26 Working Days'])->default('Actual Calendar Days');
            $table->unsignedTinyInteger('payroll_cutoff_day')->default(25);
            $table->unsignedTinyInteger('pay_day_of_month')->default(1);
            $table->enum('lop_deduction_basis', ['Calendar Days', 'Working Days'])->default('Calendar Days');
            $table->unsignedSmallInteger('ot_hourly_divisor')->default(208);
            $table->decimal('ot_multiplier', 4, 2)->default(2.00);
            $table->enum('net_pay_rounding_rule', ['Nearest Rupee', 'Floor', 'Ceiling'])->default('Nearest Rupee');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payroll_statutory_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->date('effective_from_date');
            
            // PF Configuration
            $table->decimal('pf_employee_rate', 5, 2)->default(12.00);
            $table->decimal('pf_employer_rate', 5, 2)->default(12.00);
            $table->decimal('pf_wage_ceiling', 10, 2)->default(15000.00);
            $table->boolean('pf_restrict_to_ceiling')->default(true);

            // ESI Configuration
            $table->decimal('esi_employee_rate', 5, 2)->default(0.75);
            $table->decimal('esi_employer_rate', 5, 2)->default(3.25);
            $table->decimal('esi_wage_ceiling', 10, 2)->default(21000.00);
            $table->boolean('esi_include_overtime_in_ceiling')->default(false);

            // LWF Configuration
            $table->decimal('lwf_employee_amount', 6, 2)->default(12.00);
            $table->decimal('lwf_employer_amount', 6, 2)->default(36.00);
            $table->enum('lwf_deduction_frequency', ['Monthly', 'Bi-Annual June/Dec'])->default('Bi-Annual June/Dec');

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payroll_pt_slabs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('state', 50);
            $table->enum('gender', ['Male', 'Female', 'All'])->default('All');
            $table->decimal('min_monthly_salary', 10, 2)->default(0.00);
            $table->decimal('max_monthly_salary', 10, 2)->nullable();
            $table->decimal('tax_amount', 8, 2)->default(0.00);
            $table->unsignedTinyInteger('special_month')->nullable(); // e.g. 2 for February in Maharashtra
            $table->decimal('special_tax_amount', 8, 2)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'state', 'gender'], 'idx_pt_state_gender');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_pt_slabs');
        Schema::dropIfExists('payroll_statutory_configurations');
        Schema::dropIfExists('payroll_configurations');
    }
};

