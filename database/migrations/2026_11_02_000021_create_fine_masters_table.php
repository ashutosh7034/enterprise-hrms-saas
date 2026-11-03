<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fine_masters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('fine_code', 50);
            $table->string('fine_name', 191);
            $table->enum('category', ['Asset Damage', 'Asset Loss', 'Asset Non-Return', 'Other Policy Violation']);
            $table->foreignId('asset_category_id')->nullable()->constrained('asset_categories')->nullOnDelete();
            $table->decimal('default_amount', 10, 2)->default(0.00);
            $table->date('effective_date');
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->text('remarks')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'fine_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fine_masters');
    }
};

