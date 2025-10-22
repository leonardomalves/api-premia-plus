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
        Schema::create('raffle_tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('raffle_id')->constrained('raffles');
            $table->foreignId('ticket_id')->constrained('tickets');
            $table->enum('status', ['rejected', 'cancelled', 'pending', 'confirmed', 'winner', 'loser'])->default('pending');
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['raffle_id', 'ticket_id']);
            $table->index(['raffle_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index('ticket_id'); // Otimiza query whereNotExists na busca de tickets disponíveis

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raffle_tickets');
    }
};
