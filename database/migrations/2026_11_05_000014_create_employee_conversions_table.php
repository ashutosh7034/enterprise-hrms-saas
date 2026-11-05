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
        if (!Schema::hasTable('employee_conversions')) {
            Schema::create('employee_conversions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('from_employee_type', 50)->default('Intern');
                $table->string('to_employee_type', 50)->default('Permanent');
                $table->foreignId('from_designation_id')->nullable()->constrained('designations')->nullOnDelete();
                $table->foreignId('to_designation_id')->nullable()->constrained('designations')->nullOnDelete();
                $table->foreignId('from_department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->foreignId('to_department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->foreignId('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
                $table->foreignId('to_location_id')->nullable()->constrained('locations')->nullOnDelete();
                $table->date('conversion_effective_date');
                $table->string('new_salary_structure')->nullable();
                $table->boolean('confirmation_required')->default(false);
                $table->text('remarks')->nullable();
                $table->enum('status', ['Draft', 'Pending Approval', 'Approved', 'Rejected', 'Completed', 'Cancelled'])->default('Pending Approval');
                $table->foreignId('initiated_by')->constrained('users')->cascadeOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('approved_at')->nullable();
                $table->timestamps();

                $table->index(['employee_id', 'status']);
                $table->index('conversion_effective_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_conversions');
    }
};

