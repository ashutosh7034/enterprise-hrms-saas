<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->string('code', 80); // e.g. ONBOARDING_WELCOME, LEAVE_APPROVED, PAYSLIP_AVAILABLE
            $table->string('name', 150);
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
            ]);
            $table->string('event_type', 100);
            $table->enum('channel', ['in_app', 'email', 'sms', 'whatsapp']);
            $table->string('subject', 255)->nullable();
            $table->text('body_template');
            $table->string('action_url', 255)->nullable();
            $table->boolean('is_mandatory')->default(false); // If true, cannot be disabled via user preferences
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'code', 'channel'], 'company_code_channel_unique');
            $table->index(['category', 'channel', 'is_active']);
            $table->index(['event_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};

