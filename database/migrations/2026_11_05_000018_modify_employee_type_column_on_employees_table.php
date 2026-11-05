<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change employee_type from restricted enum to VARCHAR(50) to support all enterprise lifecycle types
        // (Permanent, Intern, Contract, Probationary, Trainee, etc.)
        DB::statement("ALTER TABLE `employees` MODIFY COLUMN `employee_type` VARCHAR(50) NOT NULL DEFAULT 'Permanent'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `employees` MODIFY COLUMN `employee_type` ENUM('Permanent', 'Temporary', 'Contract', 'Trainee', 'Other') NOT NULL DEFAULT 'Permanent'");
    }
};

