<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Safely adds columns to support profile sections without row size limits.
     * All columns are nullable or have defaults to preserve existing records.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Section 1: Basic Information
            $table->string('title', 20)->nullable()->after('first_name');
            $table->string('middle_name', 100)->nullable()->after('title');
            $table->string('profile_photo_path')->nullable()->after('employee_code');
            $table->string('ticket_number', 50)->nullable()->after('profile_photo_path');
            $table->string('display_name', 150)->nullable()->after('ticket_number');
            $table->string('aka_name', 100)->nullable()->after('display_name');
            $table->string('nationality', 50)->default('Indian')->after('date_of_birth');
            $table->string('place_of_birth', 100)->nullable()->after('nationality');
            $table->string('blood_group', 10)->nullable()->after('place_of_birth');
            $table->string('identification_mark', 255)->nullable()->after('blood_group');
            $table->boolean('is_specially_abled')->default(false)->after('identification_mark');
            $table->string('disability_details', 255)->nullable()->after('is_specially_abled');
            $table->text('personal_remarks')->nullable()->after('disability_details');

            // Section 2: Employment Information
            $table->string('job_post_code', 50)->nullable()->after('designation_id');
            $table->string('grade', 50)->nullable()->after('job_post_code');
            $table->string('division', 100)->nullable()->after('grade');
            $table->string('unit', 100)->nullable()->after('division');
            $table->string('project', 100)->nullable()->after('unit');
            $table->enum('skill_level', ['Skilled', 'Semi-Skilled', 'Unskilled'])->default('Skilled')->after('project');
            $table->date('resign_offer_date')->nullable()->after('confirmation_date');
            $table->date('last_working_date')->nullable()->after('resign_offer_date');
            $table->date('fnf_retiring_date')->nullable()->after('last_working_date');
            $table->date('last_increment_date')->nullable()->after('fnf_retiring_date');
            $table->date('group_join_date')->nullable()->after('last_increment_date');
            $table->date('process_start_date')->nullable()->after('group_join_date');
            $table->string('payment_mode', 50)->default('Bank Transfer')->after('process_start_date');
            $table->text('employment_remarks')->nullable()->after('payment_mode');
            $table->string('jv_code_cr', 50)->nullable()->after('employment_remarks');
            $table->string('old_employee_code', 50)->nullable()->after('jv_code_cr');
            $table->boolean('is_account_frozen')->default(false)->after('old_employee_code');

            // Section 3: Contact Information
            $table->string('residential_phone', 25)->nullable()->after('phone');
            $table->string('personal_phone', 25)->nullable()->after('residential_phone');
            $table->string('office_phone', 25)->nullable()->after('personal_phone');
            $table->string('extension_number', 15)->nullable()->after('office_phone');
            $table->string('alternate_email', 191)->nullable()->after('email');
            $table->string('cc_email', 191)->nullable()->after('alternate_email');
            $table->string('microsoft_user', 191)->nullable()->after('cc_email');
            $table->string('google_user', 191)->nullable()->after('microsoft_user');

            // Section 6: Family Details Summary
            $table->enum('marital_status', ['Single', 'Married', 'Divorced', 'Widowed'])->nullable()->after('address');
            $table->date('marriage_anniversary_date')->nullable()->after('marital_status');
            $table->string('father_name', 100)->nullable()->after('marriage_anniversary_date');
            $table->string('mother_name', 100)->nullable()->after('father_name');
            $table->string('spouse_name', 100)->nullable()->after('mother_name');
            $table->unsignedTinyInteger('number_of_children')->default(0)->after('spouse_name');
            $table->unsignedTinyInteger('children_in_hostel')->default(0)->after('number_of_children');
            $table->text('emergency_contact_address')->nullable()->after('emergency_contact_relation');

            // Section 7: Personal Details
            $table->string('religion', 50)->nullable()->after('emergency_contact_address');
            $table->string('caste', 50)->nullable()->after('religion');
            $table->string('sub_caste', 50)->nullable()->after('caste');
            $table->decimal('height_cm', 5, 2)->nullable()->after('sub_caste');
            $table->decimal('weight_kg', 5, 2)->nullable()->after('height_cm');

            // Health & UDFs
            $table->date('health_checkup_date')->nullable()->after('weight_kg');
            $table->json('custom_fields')->nullable()->after('health_checkup_date');

            // Section 16: Profile Review & ESS Lifecycle
            $table->enum('onboarding_status', [
                'Draft',
                'Submitted for Review',
                'Under Review',
                'Corrections Requested',
                'Approved'
            ])->default('Draft')->after('status');
            $table->unsignedTinyInteger('profile_completion_percentage')->default(20)->after('onboarding_status');
            $table->text('correction_notes')->nullable()->after('profile_completion_percentage');
            $table->timestamp('submitted_at')->nullable()->after('correction_notes');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete()->after('submitted_at');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);

            $table->dropColumn([
                'title',
                'middle_name',
                'profile_photo_path',
                'ticket_number',
                'display_name',
                'aka_name',
                'nationality',
                'place_of_birth',
                'blood_group',
                'identification_mark',
                'is_specially_abled',
                'disability_details',
                'personal_remarks',
                'job_post_code',
                'grade',
                'division',
                'unit',
                'project',
                'skill_level',
                'resign_offer_date',
                'last_working_date',
                'fnf_retiring_date',
                'last_increment_date',
                'group_join_date',
                'process_start_date',
                'payment_mode',
                'employment_remarks',
                'jv_code_cr',
                'old_employee_code',
                'is_account_frozen',
                'residential_phone',
                'personal_phone',
                'office_phone',
                'extension_number',
                'alternate_email',
                'cc_email',
                'microsoft_user',
                'google_user',
                'marital_status',
                'marriage_anniversary_date',
                'father_name',
                'mother_name',
                'spouse_name',
                'number_of_children',
                'children_in_hostel',
                'emergency_contact_address',
                'religion',
                'caste',
                'sub_caste',
                'height_cm',
                'weight_kg',
                'health_checkup_date',
                'custom_fields',
                'onboarding_status',
                'profile_completion_percentage',
                'correction_notes',
                'submitted_at',
                'reviewed_by',
                'reviewed_at',
            ]);
        });
    }
};

