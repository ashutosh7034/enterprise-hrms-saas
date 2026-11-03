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
        Schema::dropIfExists('attendance_regularizations');

        Schema::create('attendance_regularizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('attendance_record_id')->nullable()->constrained('attendance_records')->nullOnDelete();
            $table->date('attendance_date');
            $table->enum('regularization_type', [
                'Missing IN Punch',
                'Missing OUT Punch',
                'Late Arrival',
                'Early Departure',
                'Business Travel / On Duty',
                'Biometric Device Failure',
                'Attendance Correction'
            ]);
            $table->dateTime('requested_in_time')->nullable();
            $table->dateTime('requested_out_time')->nullable();
            $table->text('reason'); // Mandatory reason
            $table->enum('status', [
                'Pending Manager',
                'Pending HR',
                'Approved',
                'Rejected',
                'Sent Back'
            ])->default('Pending Manager');
            $table->foreignId('applied_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('manager_action_at')->nullable();
            $table->text('manager_remarks')->nullable();
            $table->foreignId('hr_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hr_action_at')->nullable();
            $table->text('hr_remarks')->nullable();
            $table->string('original_status', 50)->nullable();
            $table->string('new_status', 50)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'attendance_date', 'status'], 'idx_att_reg_status');
            $table->index(['employee_id', 'attendance_date'], 'idx_att_reg_emp_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_regularizations');
    }
};
