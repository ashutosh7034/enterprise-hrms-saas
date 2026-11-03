<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comp_off_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('attendance_record_id')->constrained('attendance_records')->cascadeOnDelete();
            
            $table->date('qualifying_work_date');
            $table->enum('source_type', [
                'Weekly Off Work',
                'Holiday Work',
                'Plant Shutdown Overtime',
                'Emergency Duty'
            ])->default('Weekly Off Work');
            
            $table->unsignedSmallInteger('eligible_work_minutes')->default(0);
            $table->decimal('credited_days', 4, 2)->default(1.00);
            $table->decimal('availed_days', 4, 2)->default(0.00);
            $table->date('expiry_date');
            
            $table->enum('status', [
                'Pending Approval',
                'Approved',
                'Partially Availed',
                'Fully Availed',
                'Expired',
                'Rejected',
                'Cancelled'
            ])->default('Pending Approval')->index();
            
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval_remarks')->nullable();
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();

            $table->unique(['company_id', 'employee_id', 'attendance_record_id'], 'idx_compoff_unique_att');
            $table->index(['employee_id', 'status', 'expiry_date'], 'idx_compoff_emp_status_exp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comp_off_records');
    }
};

