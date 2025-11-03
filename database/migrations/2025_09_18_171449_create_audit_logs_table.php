<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_email')->nullable();
            $table->string('action');
            $table->string('model')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('url')->nullable();
            $table->string('method', 10)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['model', 'model_id']);
            $table->index(['action', 'created_at']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};



