<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity_match_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('blacklist_record_id')->nullable()->constrained('central_blacklist_records')->nullOnDelete();
            $table->enum('match_type', ['Confirmed Match', 'Potential Match']);
            $table->unsignedInteger('match_score')->default(100);
            $table->json('match_details')->nullable();
            $table->enum('status', ['Pending Review', 'Confirmed Match', 'False Positive', 'Requires More Information'])->default('Pending Review');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('resolution_remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_match_reviews');
    }
};

