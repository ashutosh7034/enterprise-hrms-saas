<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Employee Skills
        Schema::create('employee_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('skill_name', 150);
            $table->string('skill_category', 100); // Technical, Operational, Safety, Soft Skills, Leadership, Quality Control
            $table->enum('proficiency_level', ['Beginner', 'Intermediate', 'Advanced', 'Expert'])->default('Intermediate');
            $table->decimal('proficiency_score', 4, 1)->nullable(); // e.g. 8.5 / 10
            $table->decimal('years_of_experience', 4, 1)->default(0.0);
            $table->boolean('has_certification')->default(false);
            $table->string('certification_name', 150)->nullable();
            $table->string('certification_number', 100)->nullable();
            $table->string('issuing_authority', 150)->nullable();
            $table->date('certification_date')->nullable();
            $table->date('certification_expiry_date')->nullable();
            $table->string('certificate_file_path')->nullable();
            $table->date('assessment_date')->nullable();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('assessment_remarks')->nullable();
            $table->enum('verification_status', ['Pending', 'Verified', 'Rejected'])->default('Pending');
            $table->timestamps();

            $table->index(['employee_id', 'skill_category']);
        });

        // 2. Skill History Ledger (Chronological audit of all skill changes)
        Schema::create('employee_skill_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('employee_skill_id')->nullable()->constrained('employee_skills')->nullOnDelete();
            $table->string('skill_name', 150);
            $table->string('previous_level', 50)->nullable();
            $table->string('new_level', 50);
            $table->decimal('previous_score', 4, 1)->nullable();
            $table->decimal('new_score', 4, 1)->nullable();
            $table->string('change_reason', 255)->nullable();
            $table->text('assessment_notes')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_skill_histories');
        Schema::dropIfExists('employee_skills');
    }
};

