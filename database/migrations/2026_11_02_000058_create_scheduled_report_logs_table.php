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
        Schema::create('scheduled_report_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduled_report_id')->constrained('scheduled_reports')->cascadeOnDelete();
            $table->enum('status', ['Success', 'Failed', 'Skipped'])->default('Success')->index();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->text('error_message')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->timestamp('dispatched_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_report_logs');
    }
};

