<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
            $table->foreignId('role_id')->nullable()->after('company_id')->constrained('roles')->nullOnDelete();
            $table->string('employee_code', 50)->nullable()->after('name')->index();
            $table->string('phone', 25)->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('password');
            $table->boolean('can_switch_company')->default(false)->after('is_active');
            $table->softDeletes()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['role_id']);
            $table->dropColumn([
                'company_id',
                'role_id',
                'employee_code',
                'phone',
                'is_active',
                'can_switch_company',
                'deleted_at'
            ]);
        });
    }
};

