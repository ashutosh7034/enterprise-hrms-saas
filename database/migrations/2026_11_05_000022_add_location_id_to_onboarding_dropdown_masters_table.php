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
        Schema::table('onboarding_dropdown_masters', function (Blueprint $table) {
            if (!Schema::hasColumn('onboarding_dropdown_masters', 'location_id')) {
                $table->unsignedBigInteger('location_id')->nullable()->after('company_id')->index();
                $table->foreign('location_id')
                    ->references('id')
                    ->on('locations')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('onboarding_dropdown_masters', function (Blueprint $table) {
            if (Schema::hasColumn('onboarding_dropdown_masters', 'location_id')) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            }
        });
    }
};

