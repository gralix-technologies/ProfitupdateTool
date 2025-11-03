<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('import_errors', function (Blueprint $table) {
            $table->id();
            $table->string('import_session_id'); // To group errors from same import
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('row_number');
            $table->string('error_type'); // validation, processing, system
            $table->text('error_message');
            $table->json('row_data')->nullable(); // The actual row data that caused the error
            $table->json('context')->nullable(); // Additional context like field name, expected format, etc.
            $table->timestamps();
            
            $table->index(['import_session_id', 'product_id']);
            $table->index(['product_id', 'error_type']);
            $table->index('import_session_id');
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('import_errors');
    }
};



