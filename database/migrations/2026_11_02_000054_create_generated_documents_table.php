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
        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('document_templates')->nullOnDelete();
            $table->foreignId('template_version_id')->nullable()->constrained('document_template_versions')->nullOnDelete();
            $table->string('document_number', 100)->unique();
            $table->string('verification_code', 64)->unique()->index();
            $table->string('category', 100)->index();
            $table->string('document_type', 100)->index();
            $table->string('title', 255);
            $table->longText('content_snapshot_html');
            $table->json('metadata_snapshot_json')->nullable();
            $table->string('pdf_file_path', 500)->nullable();
            $table->unsignedInteger('file_size_kb')->nullable();
            $table->enum('status', [
                'Draft',
                'Pending Approval',
                'Approved',
                'Generated',
                'Issued',
                'Rejected',
                'Cancelled',
                'Archived',
            ])->default('Generated')->index();
            $table->date('effective_date')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('issued_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_documents');
    }
};

