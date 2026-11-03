<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name', 100);
            $table->text('description')->nullable();
            
            // Entitlement & Accrual Rules
            $table->boolean('is_paid')->default(true);
            $table->decimal('annual_entitlement', 5, 2)->default(0.00);
            $table->decimal('monthly_accrual_rate', 5, 2)->default(0.00);
            $table->enum('accrual_frequency', ['None', 'Monthly', 'Quarterly', 'Yearly'])->default('Monthly');
            $table->enum('accrual_timing', ['Start of Month', 'End of Month'])->default('Start of Month');
            $table->boolean('is_accrual_enabled')->default(false);
            $table->boolean('requires_confirmation')->default(false);
            
            // Service & Limit Rules
            $table->unsignedSmallInteger('min_service_days')->default(0);
            $table->unsignedSmallInteger('max_consecutive_days')->default(0)->comment('0 = Unlimited');
            $table->unsignedSmallInteger('min_notice_days')->default(0);
            $table->unsignedSmallInteger('max_backdated_days')->default(0);
            
            // Carry Forward & Expiry
            $table->boolean('is_carry_forward_enabled')->default(false);
            $table->decimal('carry_forward_limit', 5, 2)->default(0.00);
            $table->unsignedSmallInteger('expiry_months')->default(0)->comment('0 = No expiry');
            
            // Balance & Overdraft
            $table->boolean('allow_negative_balance')->default(false);
            $table->decimal('max_negative_balance', 5, 2)->default(0.00);
            
            // Policy Applicability
            $table->boolean('is_sandwich_enabled')->default(true);
            $table->boolean('is_compoff')->default(false);
            $table->boolean('is_applicable_in_notice_period')->default(false);
            $table->decimal('max_notice_period_days', 5, 2)->default(0.00);
            
            // Scopes (stored as JSON arrays of IDs)
            $table->json('applicable_categories')->nullable();
            $table->json('applicable_locations')->nullable();
            $table->json('applicable_departments')->nullable();
            $table->json('applicable_employment_types')->nullable();
            
            // Lifecycle
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code'], 'idx_leave_type_comp_code');
            $table->index(['company_id', 'status'], 'idx_leave_type_comp_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};

