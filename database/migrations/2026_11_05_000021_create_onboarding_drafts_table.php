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
        Schema::create('onboarding_drafts', function (Blueprint $table) {
            $table->id();
            $table->string('draft_id', 64)->unique()->index();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('candidate_name', 200)->nullable();
            $table->string('first_name', 100)->nullable();
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('phone', 50)->nullable();
            $table->unsignedBigInteger('location_id')->nullable()->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->unsignedSmallInteger('current_step')->default(1);
            $table->unsignedSmallInteger('completion_percentage')->default(0);
            $table->json('form_data')->nullable();
            $table->json('uploaded_files')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 30)->default('draft')->index(); // draft, submitted, abandoned
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamp('last_saved_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            if (Schema::hasTable('tenants')) {
                $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            }
            if (Schema::hasTable('locations')) {
                $table->foreign('location_id')->references('id')->on('locations')->nullOnDelete();
            }
            if (Schema::hasTable('departments')) {
                $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            }
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboarding_drafts');
    }
};

