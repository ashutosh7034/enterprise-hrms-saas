<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_visa_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            
            $table->boolean('visa_required')->default(false);
            $table->string('visa_type', 50)->nullable();
            $table->string('visa_number', 50)->nullable();
            $table->string('passport_number', 50)->nullable();
            $table->date('passport_issue_date')->nullable();
            $table->date('passport_expiry_date')->nullable();
            $table->string('passport_issuing_country', 50)->nullable();
            $table->date('visa_issue_date')->nullable();
            $table->date('visa_expiry_date')->nullable();
            $table->string('visa_issuing_country', 50)->nullable();
            $table->string('immigration_status', 50)->nullable();
            $table->string('work_permit_number', 50)->nullable();
            $table->date('work_permit_expiry_date')->nullable();
            $table->string('country_of_employment', 50)->nullable();
            $table->text('visa_remarks')->nullable();

            $table->timestamps();

            $table->unique('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_visa_details');
    }
};

