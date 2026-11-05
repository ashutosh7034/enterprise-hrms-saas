<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_family_members') && !Schema::hasColumn('employee_family_members', 'is_emergency_contact')) {
            Schema::table('employee_family_members', function (Blueprint $table) {
                $table->boolean('is_emergency_contact')->default(false)->after('is_dependent');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employee_family_members') && Schema::hasColumn('employee_family_members', 'is_emergency_contact')) {
            Schema::table('employee_family_members', function (Blueprint $table) {
                $table->dropColumn('is_emergency_contact');
            });
        }
    }
};

