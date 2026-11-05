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
        if (!Schema::hasTable('employee_confirmation_records')) {
            Schema::create('employee_confirmation_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->enum('action_type', ['Confirmation', 'Probation Extension', 'Rejection'])->default('Confirmation');
                $table->date('previous_probation_end_date')->nullable();
                $table->date('new_probation_end_date')->nullable();
                $table->date('confirmation_effective_date')->nullable();
                $table->string('confirmation_letter_path')->nullable();
                $table->text('remarks')->nullable();
                $table->enum('status', ['Confirmed', 'Probation Extended', 'Rejected'])->default('Confirmed');
                $table->foreignId('approved_by')->constrained('users')->cascadeOnDelete();
                $table->dateTime('approved_at');
                $table->timestamps();

                $table->index(['employee_id', 'action_type']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_confirmation_records');
    }
};

