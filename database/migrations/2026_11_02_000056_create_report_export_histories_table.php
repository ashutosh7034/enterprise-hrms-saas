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
        Schema::create('report_export_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('report_type', 100)->index();
            $table->string('file_format', 20); // csv, xlsx, pdf
            $table->string('file_name', 255);
            $table->string('file_path', 500)->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedInteger('file_size_kb')->default(0);
            $table->json('filters_applied_json')->nullable();
            $table->string('status', 50)->default('Completed')->index();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'report_type']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_export_histories');
    }
};

