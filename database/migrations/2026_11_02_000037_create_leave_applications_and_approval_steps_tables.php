<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('application_number', 50);
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_half_day')->default(false);
            $table->enum('half_day_type', ['First Half', 'Second Half'])->nullable();
            
            $table->decimal('requested_days', 4, 2);
            $table->decimal('calculated_days', 4, 2)->comment('Inclusive of sandwich policy days');
            $table->decimal('approved_days', 4, 2)->nullable();
            
            $table->json('sandwich_breakdown_json')->nullable();
            $table->text('reason');
            $table->string('supporting_document_path', 255)->nullable();
            
            $table->enum('status', [
                'Pending Manager',
                'Pending HOD',
                'Pending HR',
                'Approved',
                'Rejected',
                'Cancelled',
                'Withdrawn'
            ])->default('Pending Manager')->index();
            
            $table->enum('current_stage', ['Manager', 'HOD', 'HR', 'Completed'])->default('Manager');
            
            // Notice Period Override Support
            $table->boolean('is_notice_period_override')->default(false);
            $table->text('notice_period_justification')->nullable();
            
            // Multi-Stage Approver Signatures
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('manager_action_at')->nullable();
            $table->text('manager_remarks')->nullable();
            
            $table->foreignId('hod_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hod_action_at')->nullable();
            $table->text('hod_remarks')->nullable();
            
            $table->foreignId('hr_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hr_action_at')->nullable();
            $table->text('hr_remarks')->nullable();
            
            $table->text('rejection_reason')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'application_number'], 'idx_leave_app_unique_num');
            $table->index(['employee_id', 'start_date', 'end_date'], 'idx_leave_app_emp_dates');
            $table->index(['company_id', 'status'], 'idx_leave_app_comp_status');
        });

        Schema::create('leave_approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_application_id')->constrained('leave_applications')->cascadeOnDelete();
            $table->enum('stage', ['Manager', 'HOD', 'HR']);
            $table->enum('action', ['Submitted', 'Approved', 'Rejected', 'Sent Back', 'Cancelled', 'Withdrawn', 'Overridden']);
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['leave_application_id', 'stage'], 'idx_leave_app_step');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_approval_steps');
        Schema::dropIfExists('leave_applications');
    }
};

