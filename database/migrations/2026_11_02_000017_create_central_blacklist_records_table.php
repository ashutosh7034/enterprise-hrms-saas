<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('central_blacklist_records', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->date('date_of_birth')->nullable();
            $table->string('pan_number', 20)->nullable()->index();
            $table->string('aadhaar_hash', 64)->nullable()->index()->comment('Tokenized SHA-256 for duplicate matching');
            $table->string('aadhaar_masked', 20)->nullable();
            $table->string('phone', 25)->nullable()->index();
            $table->string('previous_employee_code', 50)->nullable();
            $table->foreignId('previous_company_id')->nullable()->constrained('companies')->nullOnDelete();
            
            $table->enum('reason_category', [
                'Serious Misconduct',
                'Financial / Asset Misconduct',
                'Absconding',
                'Policy Termination',
                'Fraudulent Documents',
                'Identity Fraud',
                'Other'
            ]);
            $table->text('evidence_notes');
            $table->string('supporting_document_path', 255)->nullable();
            
            // Multi-level approval workflow
            $table->enum('approval_status', ['Draft', 'Pending Review', 'Approved', 'Rejected'])->default('Pending Review');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Whitelist / Clearance
            $table->boolean('is_active')->default(true);
            $table->timestamp('whitelisted_at')->nullable();
            $table->foreignId('whitelisted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('whitelist_justification')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('central_blacklist_records');
    }
};

