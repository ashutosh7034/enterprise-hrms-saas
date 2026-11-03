<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_period_locks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            
            $table->boolean('is_locked')->default(true);
            $table->foreignId('locked_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('locked_at')->useCurrent();
            $table->text('remarks')->nullable();
            
            $table->timestamps();

            $table->unique(['company_id', 'year', 'month'], 'idx_leave_lock_period');
        });

        Schema::create('leave_period_unlock_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_period_lock_id')->constrained('leave_period_locks')->cascadeOnDelete();
            $table->foreignId('unlocked_by')->constrained('users')->cascadeOnDelete();
            $table->text('justification')->comment('Mandatory HO justification for unlocking closed period');
            $table->timestamp('unlocked_at')->useCurrent();
            
            $table->timestamp('re_locked_at')->nullable();
            $table->foreignId('re_locked_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();

            $table->index(['leave_period_lock_id'], 'idx_unlock_history_lock');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_period_unlock_histories');
        Schema::dropIfExists('leave_period_locks');
    }
};

