<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plant_shutdowns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('shutdown_code', 50);
            $table->string('title', 191);
            $table->enum('shutdown_type', [
                'Planned Maintenance',
                'Emergency',
                'Power Failure',
                'Raw Material Shortage',
                'Other'
            ]);
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->text('reason');
            $table->enum('compensation_rule', [
                'Paid Shutdown',
                'Adjust Against Leave',
                'Unpaid Shutdown',
                'Comp-Off for Duty'
            ])->default('Paid Shutdown');
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Cancelled', 'Completed'])->default('Pending');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'shutdown_code']);
            $table->index(['company_id', 'location_id', 'start_date', 'end_date', 'status'], 'idx_shutdown_lookup');
        });

        Schema::create('shutdown_crew_nominations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plant_shutdown_id')->constrained('plant_shutdowns')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('work_date');
            $table->string('role_description', 191)->nullable();
            $table->enum('compensation_type', [
                'Overtime Pay',
                'Comp-Off Credit',
                'Special Site Allowance'
            ])->default('Overtime Pay');
            $table->boolean('is_approved')->default(true);
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['plant_shutdown_id', 'employee_id', 'work_date'], 'uniq_shutdown_crew_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shutdown_crew_nominations');
        Schema::dropIfExists('plant_shutdowns');
    }
};

