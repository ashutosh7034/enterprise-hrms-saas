<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->string('document_category', 100)->default('Identity')->after('document_type');
            $table->date('expiry_date')->nullable()->after('rejection_reason');
            $table->text('remarks')->nullable()->after('expiry_date');

            $table->index(['employee_id', 'document_category']);
        });
    }

    public function down(): void
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->dropIndex(['employee_id', 'document_category']);
            $table->dropColumn(['document_category', 'expiry_date', 'remarks']);
        });
    }
};

