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
        Schema::create('overtime_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('attendance_record_id')->constrained('attendance_records')->cascadeOnDelete();
            $table->date('ot_date');
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->enum('day_type', ['Normal', 'Holiday', 'Weekly Off', 'Shutdown'])->default('Normal');
            $table->unsignedSmallInteger('raw_calculated_minutes')->default(0);
            $table->unsignedSmallInteger('rounded_ot_minutes')->default(0);
            $table->unsignedSmallInteger('requested_minutes')->default(0);
            $table->unsignedSmallInteger('approved_minutes')->default(0);
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('remarks')->nullable();
            $table->string('payroll_batch_id', 50)->nullable(); // Ready for Phase 5 interface
            $table->timestamps();

            $table->index(['company_id', 'ot_date', 'status']);
            $table->index(['employee_id', 'ot_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_records');
    }
};

