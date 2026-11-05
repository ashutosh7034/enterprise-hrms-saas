<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_dropdown_masters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('category', 60)->index();
            $table->string('name', 150);
            $table->string('code', 60);
            $table->string('description', 255)->nullable();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table->foreign('parent_id')
                ->references('id')
                ->on('onboarding_dropdown_masters')
                ->onDelete('set null');

            $table->index(['category', 'is_active', 'display_order'], 'idx_cat_active_disp_order');
            $table->index(['tenant_id', 'company_id', 'category'], 'idx_tenant_comp_cat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_dropdown_masters');
    }
};

