<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->unsignedSmallInteger('year')->index();
            
            $table->decimal('opening_balance', 6, 2)->default(0.00);
            $table->decimal('accrued_balance', 6, 2)->default(0.00);
            $table->decimal('consumed_balance', 6, 2)->default(0.00);
            $table->decimal('reserved_balance', 6, 2)->default(0.00)->comment('Locked in pending leave applications');
            $table->decimal('carry_forward_balance', 6, 2)->default(0.00);
            $table->decimal('expired_balance', 6, 2)->default(0.00);
            $table->decimal('adjustment_credit', 6, 2)->default(0.00);
            $table->decimal('adjustment_debit', 6, 2)->default(0.00);
            $table->decimal('closing_balance', 6, 2)->default(0.00);
            $table->decimal('available_balance', 6, 2)->default(0.00);
            
            $table->timestamps();

            $table->unique(['company_id', 'employee_id', 'leave_type_id', 'year'], 'idx_leave_bal_unique');
            $table->index(['employee_id', 'year'], 'idx_leave_bal_emp_year');
        });

        Schema::create('leave_balance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            
            $table->enum('transaction_type', [
                'Opening Balance',
                'Monthly Accrual',
                'Confirmation Allocation',
                'Leave Application',
                'Leave Cancellation',
                'Carry Forward',
                'Expiry',
                'Comp-Off Credit',
                'Manual Adjustment',
                'Reversal'
            ])->index();
            
            $table->date('transaction_date');
            $table->decimal('credit_days', 6, 2)->default(0.00);
            $table->decimal('debit_days', 6, 2)->default(0.00);
            $table->decimal('balance_before', 6, 2);
            $table->decimal('balance_after', 6, 2);
            
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('transaction_uuid', 64)->unique('idx_leave_tx_uuid');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamp('created_at')->useCurrent();

            $table->index(['company_id', 'employee_id', 'leave_type_id'], 'idx_leave_tx_emp_type');
            $table->index(['employee_id', 'transaction_date'], 'idx_leave_tx_emp_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balance_transactions');
        Schema::dropIfExists('leave_balances');
    }
};

