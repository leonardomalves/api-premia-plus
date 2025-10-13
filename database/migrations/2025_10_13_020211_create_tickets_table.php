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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('ticket_level')->default(1);
            $table->string('number', 20)->nullable();
            $table->decimal('price', 10, 2);
            $table->enum('status', ['active', 'used', 'expired', 'refunded'])->default('active');
            $table->softDeletes();
            $table->timestamps();

            // Índices para performance
            $table->index(['user_id', 'status']);
            $table->index(['ticket_level', 'status']);
            $table->unique('number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
