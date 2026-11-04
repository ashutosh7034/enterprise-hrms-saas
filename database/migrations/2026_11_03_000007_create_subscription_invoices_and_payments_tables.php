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
        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('restrict');
            $table->foreignId('tenant_subscription_id')->constrained('tenant_subscriptions')->onDelete('cascade');
            $table->string('invoice_number', 50)->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('INR');
            $table->enum('status', ['pending', 'paid', 'cancelled', 'refunded'])->default('pending')->index();
            $table->dateTime('issued_at');
            $table->dateTime('due_at');
            $table->dateTime('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('restrict');
            $table->foreignId('tenant_subscription_id')->constrained('tenant_subscriptions')->onDelete('cascade');
            $table->foreignId('subscription_invoice_id')->constrained('subscription_invoices')->onDelete('cascade');
            $table->string('payment_reference', 100)->unique();
            $table->string('payment_provider', 50)->default('manual_bank_transfer');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('INR');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('completed')->index();
            $table->dateTime('paid_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('subscription_invoices');
    }
};

