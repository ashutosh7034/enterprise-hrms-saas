<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_previous_employments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('company_name', 191);
            $table->string('designation', 191);
            $table->date('from_date');
            $table->date('to_date');
            $table->decimal('last_drawn_salary', 12, 2)->nullable();
            $table->text('reason_for_leaving')->nullable();
            $table->enum('exit_status', ['Normal Exit', 'Resigned', 'Terminated', 'Absconded', 'Disciplinary Exit'])->default('Normal Exit');
            $table->boolean('is_internal_company')->default(false);
            $table->string('previous_employee_code', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_previous_employments');
    }
};

