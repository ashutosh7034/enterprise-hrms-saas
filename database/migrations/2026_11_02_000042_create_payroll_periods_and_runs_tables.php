<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('cutoff_date');
            $table->enum('status', ['Open', 'Processing', 'Locked', 'Closed'])->default('Open');
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'year', 'month'], 'uniq_payroll_period_month');
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->string('run_batch_number', 50);
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            
            // Employee counts
            $table->unsignedInteger('total_eligible_employees')->default(0);
            $table->unsignedInteger('total_processed_employees')->default(0);

            // Payout Totals
            $table->decimal('total_gross_earnings', 14, 2)->default(0.00);
            $table->decimal('total_statutory_deductions', 14, 2)->default(0.00);
            $table->decimal('total_other_deductions', 14, 2)->default(0.00);
            $table->decimal('total_deductions', 14, 2)->default(0.00);
            $table->decimal('total_net_payout', 14, 2)->default(0.00);
            $table->decimal('total_employer_contributions', 14, 2)->default(0.00);

            // Workflow status
            $table->enum('status', ['Draft', 'Pre-Validation Passed', 'Pending Approval', 'Approved', 'Locked', 'Rejected', 'Disbursed'])->default('Draft');
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_remarks')->nullable();
            $table->json('validation_errors_json')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'run_batch_number'], 'uniq_payroll_run_batch');
            $table->index(['company_id', 'year', 'month', 'status'], 'idx_payroll_run_status');
        });

        Schema::create('payroll_approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->enum('stage', ['Processing', 'Review', 'HO Approval'])->default('Processing');
            $table->enum('action', ['Submitted', 'Reviewed', 'Approved', 'Rejected']);
            $table->foreignId('action_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('action_at');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_approval_steps');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('payroll_periods');
    }
};

