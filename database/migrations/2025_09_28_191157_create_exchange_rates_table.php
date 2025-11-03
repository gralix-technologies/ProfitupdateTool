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
        // Check if table already exists with different structure
        if (Schema::hasTable('exchange_rates')) {
            // Table exists, just add any missing columns if needed
            Schema::table('exchange_rates', function (Blueprint $table) {
                // Add any missing columns here if needed
                // The table already has the correct structure from previous setup
            });
        } else {
            // Create table with the structure that matches existing data
            Schema::create('exchange_rates', function (Blueprint $table) {
                $table->id();
                $table->string('currency', 3); // e.g., ZMW, USD
                $table->decimal('rate_to_base', 18, 8); // Rate to base currency
                $table->date('date'); // Effective date
                $table->timestamps();
                
                // Indexes
                $table->index(['currency', 'date']);
                $table->unique(['currency', 'date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};