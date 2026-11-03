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
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('category', 100)->index();
            $table->string('document_type', 100)->index();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->longText('content_html');
            $table->unsignedInteger('version')->default(1);
            $table->enum('status', ['Draft', 'Pending Approval', 'Approved', 'Active', 'Inactive', 'Archived'])->default('Active')->index();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->boolean('is_sensitive_salary')->default(false);
            $table->boolean('header_footer_enabled')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_templates');
    }
};

