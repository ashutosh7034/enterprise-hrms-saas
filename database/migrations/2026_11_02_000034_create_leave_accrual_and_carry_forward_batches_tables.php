<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_accrual_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('batch_number', 50);
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->foreignId('leave_type_id')->nullable()->constrained('leave_types')->nullOnDelete();
            
            $table->unsignedInteger('total_eligible_employees')->default(0);
            $table->unsignedInteger('total_credited_employees')->default(0);
            $table->decimal('total_days_credited', 10, 2)->default(0.00);
            
            $table->enum('status', ['Initiated', 'Completed', 'Failed'])->default('Initiated');
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('execution_notes')->nullable();
            
            $table->timestamps();

            $table->unique(['company_id', 'year', 'month', 'leave_type_id'], 'idx_accrual_batch_unique');
        });

        Schema::create('leave_carry_forward_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('batch_number', 50);
            $table->unsignedSmallInteger('from_year');
            $table->unsignedSmallInteger('to_year');
            $table->foreignId('leave_type_id')->nullable()->constrained('leave_types')->nullOnDelete();
            
            $table->boolean('is_dry_run')->default(false);
            $table->unsignedInteger('total_employees_processed')->default(0);
            $table->decimal('total_days_carried_forward', 10, 2)->default(0.00);
            $table->decimal('total_days_expired', 10, 2)->default(0.00);
            
            $table->enum('status', ['Draft', 'Simulated', 'Completed', 'Failed'])->default('Draft');
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('execution_log_json')->nullable();
            
            $table->timestamps();

            $table->index(['company_id', 'from_year', 'to_year'], 'idx_cf_batch_comp_years');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_carry_forward_batches');
        Schema::dropIfExists('leave_accrual_batches');
    }
};

