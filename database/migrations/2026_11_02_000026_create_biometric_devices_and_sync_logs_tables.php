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
        Schema::create('biometric_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->string('device_identifier', 100); // e.g. "PUNE-PLANT-GATE-01"
            $table->string('device_name', 150);
            $table->string('serial_number', 100);
            $table->string('ip_address', 50)->nullable();
            $table->unsignedInteger('port')->default(4370);
            $table->enum('device_type', [
                'Biometric Fingerprint',
                'Facial Recognition',
                'Iris Scanner',
                'RFID / Card Reader',
                'Multi-Modal'
            ])->default('Biometric Fingerprint');
            $table->enum('protocol', [
                'ZKTeco Push / ADMS',
                'Direct IP Poll',
                'REST Webhook',
                'SOAP / XML',
                'Manual CSV Import'
            ])->default('REST Webhook');
            $table->enum('status', ['Active', 'Shutdown', 'Inactive'])->default('Active');
            $table->enum('health_status', ['Healthy', 'Warning', 'Offline', 'Sync Failure'])->default('Healthy');
            $table->string('secret_token', 128)->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'device_identifier']);
            $table->index(['company_id', 'location_id', 'status']);
        });

        Schema::create('biometric_device_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('biometric_device_id')->constrained('biometric_devices')->cascadeOnDelete();
            $table->timestamp('sync_start_time')->useCurrent();
            $table->timestamp('sync_end_time')->nullable();
            $table->unsignedInteger('records_received')->default(0);
            $table->unsignedInteger('records_processed')->default(0);
            $table->unsignedInteger('records_duplicate')->default(0);
            $table->unsignedInteger('records_failed')->default(0);
            $table->enum('status', ['Success', 'Partial', 'Failed'])->default('Success');
            $table->text('error_message')->nullable();
            $table->text('raw_payload_preview')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biometric_device_sync_logs');
        Schema::dropIfExists('biometric_devices');
    }
};

