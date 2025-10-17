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
        Schema::table('tickets', function (Blueprint $table) {
            // Remover a foreign key constraint incorreta (orders)
            $table->dropForeign(['raffle_id']);
            
            // Adicionar a foreign key constraint correta (raffles)
            $table->foreign('raffle_id')->references('id')->on('raffles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Remover a foreign key constraint correta
            $table->dropForeign(['raffle_id']);
            
            // Restaurar a foreign key constraint incorreta
            $table->foreign('raffle_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }
};
