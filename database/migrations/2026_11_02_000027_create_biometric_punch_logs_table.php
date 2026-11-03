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
        Schema::create('biometric_punch_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('biometric_device_id')->nullable()->constrained('biometric_devices')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('biometric_user_id', 100)->index(); // Device PIN, Card ID, or Gate Pass
            $table->dateTime('punch_time')->index(); // Actual original timestamp from device
            $table->dateTime('sync_time')->index(); // Ingestion / receipt timestamp
            $table->enum('direction', ['IN', 'OUT', 'BREAK_IN', 'BREAK_OUT', 'UNKNOWN'])->default('UNKNOWN');
            $table->string('punch_fingerprint', 64)->index(); // SHA-256 hash of (company:device_user:punch_time:direction)
            $table->longText('raw_payload')->nullable();
            $table->string('source', 50)->default('Webhook'); // Webhook, CLI, Polling, Manual CSV
            $table->enum('processing_status', ['Raw', 'Processed', 'Ignored_Duplicate', 'Error', 'Reprocessed'])->default('Raw');
            $table->boolean('is_duplicate')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'punch_time']);
            $table->index(['company_id', 'employee_id', 'punch_time']);
            $table->index(['company_id', 'processing_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biometric_punch_logs');
    }
};

