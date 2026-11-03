<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('employee_code', 50)->index();
            $table->string('gate_pass_number', 100)->nullable()->index();
            $table->enum('employment_channel', ['Direct', 'Contractor'])->default('Direct');
            $table->foreignId('contractor_id')->nullable()->constrained('contractors')->nullOnDelete();
            $table->enum('employee_type', ['Permanent', 'Temporary', 'Contract', 'Trainee', 'Other'])->default('Permanent');
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('designation_id')->constrained('designations')->cascadeOnDelete();
            $table->foreignId('reporting_manager_id')->nullable()->constrained('employees')->nullOnDelete();
            
            // Personal Details
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 191)->nullable()->index();
            $table->string('phone', 25)->index();
            $table->enum('gender', ['Male', 'Female', 'Other'])->default('Male');
            $table->date('date_of_birth');
            $table->date('date_of_joining');
            $table->date('probation_end_date')->nullable();
            $table->date('confirmation_date')->nullable();
            
            // Identity Storage (Aadhaar protected + Tokenized)
            $table->string('aadhaar_hash', 64)->nullable()->index()->comment('SHA-256 tokenized hash for duplicate matching');
            $table->string('aadhaar_masked', 20)->nullable()->comment('XXXX-XXXX-1234');
            $table->string('pan_number', 20)->nullable()->index();
            $table->text('address')->nullable();
            
            // Emergency Contact
            $table->string('emergency_contact_name', 100)->nullable();
            $table->string('emergency_contact_phone', 25)->nullable();
            $table->string('emergency_contact_relation', 50)->nullable();
            
            // Lifecycle and eKYC Statuses
            $table->enum('status', ['Draft', 'Pending Verification', 'Active', 'Inactive', 'Resigned', 'Terminated', 'Transferred'])->default('Draft');
            $table->enum('ekyc_status', ['Not Initiated', 'Pending', 'Resubmission Required', 'Verified', 'Exceptionally Verified', 'Verification Failed', 'Rejected', 'On Hold'])->default('Not Initiated');
            $table->date('ekyc_resubmission_deadline')->nullable();
            $table->boolean('is_salary_activated')->default(false);
            
            // Rehire Linkage (linking to previous employee ID without reusing ID)
            $table->foreignId('rehire_of_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'employee_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

