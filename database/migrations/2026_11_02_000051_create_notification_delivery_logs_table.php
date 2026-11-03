<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->uuid('notification_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->enum('channel', ['in_app', 'email', 'sms', 'whatsapp']);
            $table->string('recipient_address', 180); // Email or phone (masked where applicable)
            $table->enum('status', ['queued', 'dispatched', 'delivered', 'failed', 'skipped'])->default('queued');
            $table->string('provider_name', 50)->default('mock');
            $table->string('provider_message_id', 120)->nullable();
            $table->json('payload')->nullable(); // Subject, body, metadata
            $table->text('provider_response')->nullable();
            $table->text('failure_reason')->nullable();
            $table->unsignedInteger('attempt_number')->default(1);
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->foreign('notification_id')->references('id')->on('notifications')->onDelete('cascade');
            $table->index(['company_id', 'channel', 'status'], 'comp_chan_stat_idx');
            $table->index(['user_id', 'created_at']);
            $table->index(['employee_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_delivery_logs');
    }
};

