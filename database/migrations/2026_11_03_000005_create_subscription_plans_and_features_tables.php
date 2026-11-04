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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('plan_code', 30)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->enum('billing_interval', ['monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('currency', 10)->default('INR');
            $table->unsignedInteger('employee_limit')->default(50);
            $table->unsignedInteger('company_limit')->default(1);
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('subscription_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->onDelete('cascade');
            $table->string('feature_code', 50)->index();
            $table->string('feature_name', 100);
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('limit_value')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['subscription_plan_id', 'feature_code'], 'plan_feature_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_features');
        Schema::dropIfExists('subscription_plans');
    }
};

