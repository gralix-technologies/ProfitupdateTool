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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique(); // e.g., ZMW, USD, EUR
            $table->string('name'); // e.g., Zambian Kwacha
            $table->string('symbol', 10); // e.g., K, $, €
            $table->string('display_name'); // e.g., Zambian Kwacha (ZMW) - K
            $table->boolean('is_base_currency')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('decimal_places')->default(2);
            $table->string('thousands_separator', 1)->default(',');
            $table->string('decimal_separator', 1)->default('.');
            $table->enum('symbol_position', ['before', 'after'])->default('before');
            $table->timestamps();
            
            $table->index(['is_base_currency', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};