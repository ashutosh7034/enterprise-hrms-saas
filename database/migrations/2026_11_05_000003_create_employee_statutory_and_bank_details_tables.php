<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Statutory & Compliance Details
        Schema::create('employee_statutory_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            
            // PAN / UAN / PRAN
            $table->string('uan_number', 50)->nullable();
            $table->string('gratuity_code', 50)->nullable();
            $table->string('pran', 50)->nullable();

            // Professional Tax
            $table->boolean('has_pt')->default(true);
            $table->string('pt_number', 50)->nullable();

            // Provident Fund
            $table->boolean('has_pf')->default(true);
            $table->date('pf_join_date')->nullable();
            $table->string('pf_number', 50)->nullable();
            $table->string('fpf_number', 50)->nullable();
            $table->string('pf_new_version', 50)->nullable();
            $table->decimal('vpf_percentage', 5, 2)->nullable();
            $table->string('pf_remarks', 255)->nullable();
            $table->string('pf_nationality', 50)->nullable();
            $table->string('pf_country', 50)->nullable();
            $table->boolean('is_international_worker')->default(false);
            $table->string('pf_previous_member_id', 50)->nullable();
            $table->string('npr_number', 50)->nullable();
            $table->string('election_card_number', 50)->nullable();
            $table->string('ration_card_number', 50)->nullable();
            $table->string('statutory_education_qualification', 100)->nullable();

            // ESIC
            $table->boolean('has_esic')->default(true);
            $table->string('esic_number', 50)->nullable();
            $table->string('esic_old_number', 50)->nullable();
            $table->string('esic_imp_code', 50)->nullable();
            $table->string('esic_imp_name', 150)->nullable();

            $table->timestamps();

            $table->unique('employee_id');
        });

        // 2. Banking Details (Segregated for Page 94 RBAC compensation isolation)
        Schema::create('employee_bank_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            
            $table->string('bank_passbook_name', 150)->nullable();
            $table->string('pan_card_name', 150)->nullable();
            $table->string('aadhaar_card_name', 150)->nullable();
            $table->string('bank_name', 150)->nullable();
            $table->string('bank_branch', 150)->nullable();
            $table->string('bank_ifsc', 20)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->enum('bank_account_type', ['Savings', 'Salary', 'Current'])->default('Salary');
            $table->string('payment_mode', 50)->default('Bank Transfer');

            $table->timestamps();

            $table->unique('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_bank_details');
        Schema::dropIfExists('employee_statutory_details');
    }
};

