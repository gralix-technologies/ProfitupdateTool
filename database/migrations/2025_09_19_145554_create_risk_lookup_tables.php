<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('risk_weights', function (Blueprint $table) {
            $table->id();
            $table->string('credit_rating', 10);
            $table->string('collateral_type', 50);
            $table->decimal('risk_weight_percent', 5, 2); // e.g., 100.00 for 100%
            $table->timestamps();
            
            $table->unique(['credit_rating', 'collateral_type']);
        });

        Schema::create('pd_lookup', function (Blueprint $table) {
            $table->id();
            $table->string('credit_rating', 10)->unique();
            $table->decimal('pd_default', 7, 6);
            $table->timestamps();
        });

        Schema::create('lgd_lookup', function (Blueprint $table) {
            $table->id();
            $table->string('collateral_type', 50)->unique();
            $table->decimal('lgd_default', 7, 6);
            $table->timestamps();
        });

        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('currency', 3);
            $table->decimal('rate_to_base', 18, 8);
            $table->date('date');
            $table->timestamps();
            
            $table->unique(['currency', 'date']);
        });

        Schema::create('configurations', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('configurations');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('lgd_lookup');
        Schema::dropIfExists('pd_lookup');
        Schema::dropIfExists('risk_weights');
    }
};



