<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('product_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('customer_id');
            $table->json('data'); // Dynamic data based on product field definitions
            $table->decimal('amount', 15, 2)->nullable(); // Common field for financial amounts
            $table->date('effective_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            
            $table->index(['customer_id', 'product_id']);
            $table->index(['product_id', 'status']);
            $table->index('effective_date');
            
            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade');
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('product_data');
    }
};



