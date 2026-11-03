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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('code', 50);
            $table->string('name', 100);
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('break_duration_minutes')->default(45);
            $table->unsignedSmallInteger('grace_period_minutes')->default(15);
            $table->unsignedSmallInteger('half_day_threshold_minutes')->default(240); // 4 hours
            $table->unsignedSmallInteger('full_day_threshold_minutes')->default(480); // 8 hours
            $table->boolean('is_overnight')->default(false); // crosses midnight e.g., 22:00 to 06:00
            $table->json('weekly_off_days')->nullable(); // e.g. ["Sunday"]
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};

