<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_ekyc_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->unsignedInteger('attempt_number')->default(1);
            $table->enum('verification_mode', ['Manual Verification', 'Simulated eKYC', 'External API'])->default('Simulated eKYC');
            $table->enum('result', ['Success', 'Failed', 'Exception Approved', 'Rejected']);
            $table->text('failure_reason')->nullable();
            $table->date('resubmission_due_date')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_ekyc_attempts');
    }
};

