<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Enforce database-level immutability on MySQL for audit_logs table
        if (DB::getDriverName() === 'mysql') {
            DB::unprepared("
                CREATE TRIGGER prevent_audit_logs_update BEFORE UPDATE ON audit_logs
                FOR EACH ROW
                BEGIN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Audit records are immutable and cannot be updated.';
                END;
            ");

            DB::unprepared("
                CREATE TRIGGER prevent_audit_logs_delete BEFORE DELETE ON audit_logs
                FOR EACH ROW
                BEGIN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Audit records are permanent and cannot be deleted.';
                END;
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::unprepared("DROP TRIGGER IF EXISTS prevent_audit_logs_update;");
            DB::unprepared("DROP TRIGGER IF EXISTS prevent_audit_logs_delete;");
        }
    }
};

