<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name', 100);
            $table->enum('component_type', ['Earning', 'Deduction', 'Employer Contribution', 'Reimbursement'])->default('Earning');
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_statutory')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'code'], 'uniq_comp_salary_code');
        });

        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_target_ot')->default(false);
            $table->decimal('target_ot_amount', 10, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code'], 'uniq_struct_company_code');
        });

        Schema::create('salary_structure_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_structure_id')->constrained('salary_structures')->cascadeOnDelete();
            $table->foreignId('salary_component_id')->constrained('salary_components')->cascadeOnDelete();
            $table->enum('calculation_type', ['Fixed Amount', 'Percentage of Component', 'Formula'])->default('Fixed Amount');
            $table->decimal('percentage', 5, 2)->nullable();
            $table->foreignId('percentage_of_component_id')->nullable()->constrained('salary_components')->nullOnDelete();
            $table->string('formula', 255)->nullable();
            $table->decimal('default_amount', 10, 2)->default(0.00);
            $table->boolean('is_mandatory')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('employee_salary_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('salary_structure_id')->constrained('salary_structures')->cascadeOnDelete();
            $table->date('effective_from_date');
            $table->date('effective_to_date')->nullable();
            $table->decimal('ctc_annual', 12, 2)->default(0.00);
            $table->decimal('gross_monthly', 10, 2)->default(0.00);
            $table->json('component_values_json')->nullable();
            $table->boolean('is_target_ot')->default(false);
            $table->decimal('target_ot_amount', 10, 2)->default(0.00);
            $table->boolean('is_pf_eligible')->default(true);
            $table->boolean('is_esi_eligible')->default(true);
            $table->enum('status', ['Draft', 'Approved', 'Superseded'])->default('Draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('revision_notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'employee_id', 'status'], 'idx_emp_sal_assign_status');
        });

        Schema::create('salary_revision_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('previous_assignment_id')->nullable()->constrained('employee_salary_assignments')->nullOnDelete();
            $table->foreignId('new_assignment_id')->constrained('employee_salary_assignments')->cascadeOnDelete();
            $table->date('effective_from_date');
            $table->decimal('old_ctc_annual', 12, 2)->default(0.00);
            $table->decimal('new_ctc_annual', 12, 2)->default(0.00);
            $table->decimal('increment_percentage', 5, 2)->default(0.00);
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_revision_histories');
        Schema::dropIfExists('employee_salary_assignments');
        Schema::dropIfExists('salary_structure_components');
        Schema::dropIfExists('salary_structures');
        Schema::dropIfExists('salary_components');
    }
};

