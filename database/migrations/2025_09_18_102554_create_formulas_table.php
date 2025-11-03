<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('formulas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('expression'); // The formula expression
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('cascade');
            $table->json('parameters')->nullable(); // Formula parameters and metadata
            $table->text('description')->nullable();
            $table->enum('return_type', ['numeric', 'text', 'boolean', 'date'])->default('numeric');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['product_id', 'is_active']);
            $table->index('created_by');
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('formulas');
    }
};



