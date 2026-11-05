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
        if (!Schema::hasTable('employee_code_configurations')) {
            Schema::create('employee_code_configurations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('prefix', 50)->default('EMP');
                $table->string('company_prefix', 50)->nullable();
                $table->string('branch_prefix', 50)->nullable();
                $table->string('department_prefix', 50)->nullable();
                $table->string('financial_year_prefix', 50)->nullable();
                $table->unsignedTinyInteger('number_length')->default(4);
                $table->unsignedBigInteger('starting_sequence_number')->default(1001);
                $table->string('separator', 10)->default('-');
                $table->string('format_template', 150)->default('{COMPANY}-{PREFIX}-{SEQUENCE}');
                $table->boolean('reset_sequence_annually')->default(false);
                $table->boolean('reset_sequence_per_branch')->default(false);
                $table->boolean('reset_sequence_per_company')->default(true);
                $table->boolean('allow_manual_employee_code')->default(false);
                $table->enum('uniqueness_scope', ['company', 'tenant', 'global'])->default('company');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['company_id', 'is_active']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_code_configurations');
    }
};

