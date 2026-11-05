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
        if (!Schema::hasTable('employee_branch_transfers')) {
            Schema::create('employee_branch_transfers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('from_location_id')->constrained('locations')->cascadeOnDelete();
                $table->foreignId('to_location_id')->constrained('locations')->cascadeOnDelete();
                $table->foreignId('from_department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->foreignId('to_department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->foreignId('from_designation_id')->nullable()->constrained('designations')->nullOnDelete();
                $table->foreignId('to_designation_id')->nullable()->constrained('designations')->nullOnDelete();
                $table->foreignId('from_reporting_manager_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->foreignId('to_reporting_manager_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->enum('transfer_type', ['Permanent', 'Temporary'])->default('Permanent');
                $table->date('transfer_effective_date');
                $table->date('transfer_end_date')->nullable();
                $table->text('reason');
                $table->text('remarks')->nullable();
                $table->enum('status', ['Draft', 'Pending Approval', 'Approved', 'Rejected', 'Completed', 'Cancelled'])->default('Pending Approval');
                $table->foreignId('initiated_by')->constrained('users')->cascadeOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('approved_at')->nullable();
                $table->timestamps();

                $table->index(['employee_id', 'status']);
                $table->index('transfer_effective_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_branch_transfers');
    }
};

