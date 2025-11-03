<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('dashboards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('layout')->nullable(); // Widget positions and configurations
            $table->json('filters')->nullable(); // Default filters for the dashboard
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['user_id', 'is_active']);
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('dashboards');
    }
};



