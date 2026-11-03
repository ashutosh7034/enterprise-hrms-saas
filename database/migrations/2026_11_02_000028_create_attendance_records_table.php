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
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->dateTime('first_in')->nullable();
            $table->dateTime('last_out')->nullable();
            $table->unsignedSmallInteger('total_punches')->default(0);
            $table->json('punch_records_json')->nullable();
            $table->unsignedSmallInteger('total_work_minutes')->default(0);
            $table->unsignedSmallInteger('break_minutes')->default(0);
            $table->unsignedSmallInteger('actual_worked_minutes')->default(0);
            $table->unsignedSmallInteger('late_arrival_minutes')->default(0);
            $table->unsignedSmallInteger('early_departure_minutes')->default(0);
            $table->unsignedSmallInteger('overtime_minutes')->default(0);
            $table->enum('status', [
                'Present',
                'Half Day',
                'Absent',
                'Weekly Off',
                'Holiday',
                'Missing Punch',
                'Site Shutdown',
                'Worked During Shutdown',
                'On Duty',
                'Regularized'
            ])->default('Absent');
            $table->enum('regularization_status', ['None', 'Pending', 'Approved', 'Rejected'])->default('None');
            $table->unsignedBigInteger('regularization_id')->nullable();
            $table->boolean('is_overnight')->default(false);
            $table->boolean('is_reconciled')->default(false);
            $table->text('reconciliation_notes')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'employee_id', 'attendance_date'], 'uniq_emp_attendance_date');
            $table->index(['company_id', 'attendance_date', 'status']);
            $table->index(['employee_id', 'attendance_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};

