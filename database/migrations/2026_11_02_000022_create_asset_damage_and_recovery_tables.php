<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_damage_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_allocation_id')->constrained('asset_allocations')->cascadeOnDelete();
            $table->foreignId('fine_master_id')->nullable()->constrained('fine_masters')->nullOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            
            $table->decimal('assessed_amount', 10, 2);
            $table->decimal('depreciated_value', 10, 2)->default(0.00);
            $table->enum('depreciation_strategy', ['Straight-Line', 'Written-Down Value', 'Fixed Assessment'])->default('Straight-Line');
            $table->text('condition_evidence')->nullable();
            $table->text('investigation_notes')->nullable();
            
            // Approval workflow
            $table->enum('status', ['Pending Approval', 'Approved', 'Waived', 'Rejected'])->default('Pending Approval');
            $table->foreignId('assessed_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            
            // Waiver
            $table->boolean('waiver_requested')->default(false);
            $table->text('waiver_reason')->nullable();
            $table->decimal('waived_amount', 10, 2)->default(0.00);
            $table->foreignId('waiver_approved_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Recovery terms
            $table->decimal('net_recovery_amount', 10, 2);
            $table->enum('recovery_method', ['Payroll Queue', 'Direct Payment'])->default('Direct Payment');
            $table->unsignedInteger('installment_count')->default(1);
            
            $table->timestamps();
        });

        Schema::create('fine_recovery_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('damage_assessment_id')->constrained('asset_damage_assessments')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            
            $table->unsignedInteger('installment_number')->default(1);
            $table->decimal('amount', 10, 2);
            $table->enum('payment_channel', ['Direct Payment', 'Payroll Deduction Queue']);
            $table->enum('status', ['Pending', 'Paid', 'Queued for Phase 5 Payroll', 'Waived'])->default('Pending');
            $table->date('paid_date')->nullable();
            $table->string('receipt_reference', 100)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fine_recovery_records');
        Schema::dropIfExists('asset_damage_assessments');
    }
};

