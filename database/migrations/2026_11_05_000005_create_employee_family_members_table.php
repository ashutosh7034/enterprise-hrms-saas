<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('relationship', 50); // Father, Mother, Spouse, Son, Daughter, Brother, Sister, Other
            $table->date('date_of_birth')->nullable();
            $table->string('contact_number', 25)->nullable();
            $table->string('occupation', 100)->nullable();
            $table->boolean('is_dependent')->default(false);
            $table->string('remarks', 255)->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'relationship']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_family_members');
    }
};

