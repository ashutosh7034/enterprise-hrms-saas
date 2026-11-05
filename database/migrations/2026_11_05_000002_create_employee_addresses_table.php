<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            
            // Current Address
            $table->string('current_flat_plot', 100)->nullable();
            $table->string('current_street', 150)->nullable();
            $table->string('current_area', 150)->nullable();
            $table->string('current_city', 100)->nullable();
            $table->string('current_district', 100)->nullable();
            $table->string('current_state', 100)->nullable();
            $table->string('current_pincode', 20)->nullable();
            $table->string('current_country', 50)->default('India');
            
            // Permanent Address
            $table->boolean('permanent_same_as_current')->default(true);
            $table->string('permanent_flat_plot', 100)->nullable();
            $table->string('permanent_street', 150)->nullable();
            $table->string('permanent_area', 150)->nullable();
            $table->string('permanent_city', 100)->nullable();
            $table->string('permanent_district', 100)->nullable();
            $table->string('permanent_state', 100)->nullable();
            $table->string('permanent_pincode', 20)->nullable();
            $table->string('permanent_country', 50)->default('India');
            
            $table->timestamps();

            $table->unique('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_addresses');
    }
};

