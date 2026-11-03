<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 50)->nullable();
            $table->date('date');
            
            $table->enum('holiday_type', [
                'National Holiday',
                'Regional Holiday',
                'Festival Holiday',
                'Company Holiday',
                'Optional Holiday',
                'Site-Specific Holiday',
                'Shutdown Holiday'
            ])->default('Festival Holiday');
            
            // Scopes (null = applies universally within the company)
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            
            $table->boolean('is_ground_staff_applicable')->default(true);
            $table->boolean('is_paid')->default(true);
            $table->unsignedSmallInteger('year');
            $table->text('description')->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            
            $table->timestamps();

            $table->index(['company_id', 'date'], 'idx_holidays_comp_date');
            $table->index(['company_id', 'year'], 'idx_holidays_comp_year');
        });

        Schema::create('sandwich_leave_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->nullable()->constrained('leave_types')->nullOnDelete()->comment('Null applies as default');
            $table->string('policy_name', 100);
            
            $table->boolean('is_enabled')->default(true);
            $table->boolean('include_intervening_weekly_offs')->default(true);
            $table->boolean('include_intervening_holidays')->default(true);
            
            // Scope overrides
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->unsignedSmallInteger('priority')->default(1);
            
            $table->timestamps();

            $table->index(['company_id', 'leave_type_id', 'is_enabled'], 'idx_sandwich_policy_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sandwich_leave_policies');
        Schema::dropIfExists('holidays');
    }
};

