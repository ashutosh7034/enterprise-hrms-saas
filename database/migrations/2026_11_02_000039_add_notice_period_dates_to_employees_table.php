<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('notice_period_start_date')->nullable()->after('confirmation_date');
            $table->date('notice_period_end_date')->nullable()->after('notice_period_start_date');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['notice_period_start_date', 'notice_period_end_date']);
        });
    }
};

