<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('qualification_level', 50); // 10th/SSC, 12th/HSC, Diploma, Bachelor's, Master's, Doctorate, ITI, Certification
            $table->string('qualification_name', 150);
            $table->string('specialization', 150)->nullable();
            $table->string('institution_name', 200);
            $table->string('board_university', 200)->nullable();
            $table->string('country', 50)->default('India');
            $table->string('state', 100)->nullable();
            $table->unsignedSmallInteger('start_year')->nullable();
            $table->unsignedSmallInteger('passing_year')->nullable();
            $table->string('percentage_or_cgpa', 20)->nullable();
            $table->string('grade', 20)->nullable();
            $table->enum('result_status', ['Passed', 'Pursuing', 'Incomplete'])->default('Passed');
            $table->string('certificate_number', 100)->nullable();
            $table->string('certificate_file_path')->nullable();
            $table->string('marksheet_file_path')->nullable();
            $table->string('additional_doc_file_path')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'qualification_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_educations');
    }
};

