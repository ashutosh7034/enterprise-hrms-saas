<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->enum('category', [
                'Onboarding',
                'Attendance',
                'Shift',
                'Leave',
                'Payroll',
                'Loans',
                'Settlement',
                'Compliance',
                'Security',
            ])->default('Compliance');
            $table->string('event_type', 100)->nullable();
            $table->enum('channel', ['in_app', 'email', 'sms', 'whatsapp'])->default('in_app');
            $table->foreignId('template_id')->nullable()->constrained('notification_templates')->nullOnDelete();
            $table->text('data'); // JSON payload: title, body, action_url, metadata, icon
            $table->enum('delivery_status', ['pending', 'dispatched', 'delivered', 'failed', 'skipped'])->default('delivered');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->text('failure_reason')->nullable();
            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->timestamps();

            $table->index(['company_id', 'delivery_status']);
            $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
            $table->index(['category', 'created_at']);
            $table->index(['channel', 'delivery_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

