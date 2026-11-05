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
        if (!Schema::hasTable('employee_lifecycle_events')) {
            Schema::create('employee_lifecycle_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('event_type', 100);
                $table->json('previous_value')->nullable();
                $table->json('new_value')->nullable();
                $table->date('effective_date')->nullable();
                $table->string('reason')->nullable();
                $table->text('remarks')->nullable();
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('approved_at')->nullable();
                $table->timestamps();

                $table->index(['employee_id', 'event_type']);
                $table->index('effective_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_lifecycle_events');
    }
};

