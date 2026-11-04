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
        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('restrict');
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->onDelete('restrict');
            $table->string('subscription_code', 50)->unique();
            $table->enum('status', [
                'pending',
                'trial',
                'active',
                'grace_period',
                'expired',
                'suspended',
                'cancelled',
            ])->default('active')->index();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->dateTime('grace_period_ends_at')->nullable();
            $table->dateTime('activated_at')->nullable();
            $table->dateTime('suspended_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->enum('renewal_type', ['manual', 'auto'])->default('manual');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by_platform_user_id')->nullable()->constrained('platform_users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_subscriptions');
    }
};

