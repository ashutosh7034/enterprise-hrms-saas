<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_channel_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->enum('channel', ['in_app', 'email', 'sms', 'whatsapp']);
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_mock')->default(true);
            $table->string('provider_name')->default('mock'); // mock, smtp, log, twilio, msg91, meta_whatsapp, etc.
            $table->text('credentials')->nullable(); // JSON encrypted or serialised credentials
            $table->json('settings')->nullable(); // rate limits, sender id, whatsapp phone id, etc.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'channel'], 'company_channel_unique');
            $table->index(['channel', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_channel_configurations');
    }
};

