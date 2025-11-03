<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->enum('type', ['KPI', 'Table', 'PieChart', 'BarChart', 'LineChart', 'Heatmap']);
            $table->json('configuration'); // Widget-specific configuration
            $table->json('position'); // x, y, width, height for drag-and-drop
            $table->json('data_source')->nullable(); // Query or data source configuration
            $table->boolean('is_active')->default(true);
            $table->integer('order_index')->default(0);
            $table->timestamps();
            
            $table->index(['dashboard_id', 'is_active']);
            $table->index('order_index');
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('widgets');
    }
};



