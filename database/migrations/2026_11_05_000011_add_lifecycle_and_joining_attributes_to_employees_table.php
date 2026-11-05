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
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'joining_location_id')) {
                $table->foreignId('joining_location_id')->nullable()->after('location_id')->constrained('locations')->nullOnDelete();
            }
            if (!Schema::hasColumn('employees', 'joining_department_id')) {
                $table->foreignId('joining_department_id')->nullable()->after('department_id')->constrained('departments')->nullOnDelete();
            }
            if (!Schema::hasColumn('employees', 'joining_designation_id')) {
                $table->foreignId('joining_designation_id')->nullable()->after('designation_id')->constrained('designations')->nullOnDelete();
            }
            if (!Schema::hasColumn('employees', 'probation_start_date')) {
                $table->date('probation_start_date')->nullable()->after('date_of_joining');
            }
            if (!Schema::hasColumn('employees', 'confirmation_due_date')) {
                $table->date('confirmation_due_date')->nullable()->after('probation_end_date');
            }
            if (!Schema::hasColumn('employees', 'confirmation_status')) {
                $table->string('confirmation_status', 50)->default('Not Applicable')->after('confirmation_date');
            }
            if (!Schema::hasColumn('employees', 'initial_employment_status')) {
                $table->string('initial_employment_status', 50)->nullable()->after('employee_type');
            }
            if (!Schema::hasColumn('employees', 'confirmation_remarks')) {
                $table->text('confirmation_remarks')->nullable()->after('confirmation_status');
            }
            if (!Schema::hasColumn('employees', 'confirmation_letter_path')) {
                $table->string('confirmation_letter_path')->nullable()->after('confirmation_remarks');
            }
            if (!Schema::hasColumn('employees', 'code_generation_method')) {
                $table->string('code_generation_method', 30)->default('Automatic')->after('employee_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['joining_location_id']);
            $table->dropForeign(['joining_department_id']);
            $table->dropForeign(['joining_designation_id']);
            $table->dropColumn([
                'joining_location_id',
                'joining_department_id',
                'joining_designation_id',
                'probation_start_date',
                'confirmation_due_date',
                'confirmation_status',
                'initial_employment_status',
                'confirmation_remarks',
                'confirmation_letter_path',
                'code_generation_method',
            ]);
        });
    }
};

