<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_id')->unique();
            $table->string('name');
            $table->text('email')->nullable(); // Using text for encrypted data
            $table->text('phone')->nullable(); // Using text for encrypted data
            $table->json('demographics')->nullable();
            $table->string('branch_code', 50)->nullable();
            $table->decimal('total_loans_outstanding', 15, 2)->default(0);
            $table->decimal('total_deposits', 15, 2)->default(0);
            $table->decimal('npl_exposure', 15, 2)->default(0);
            $table->decimal('profitability', 10, 2)->default(0);
            $table->enum('risk_level', ['Low', 'Medium', 'High'])->default('Medium');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['branch_code', 'risk_level']);
            $table->index('customer_id');
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};



