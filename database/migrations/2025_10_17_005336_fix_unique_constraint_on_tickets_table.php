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
            // Remover a constraint unique de (user_id, number)
            $table->dropUnique(['user_id', 'number']);
            
            // Adicionar constraint unique para (raffle_id, number)
            // Cada número deve ser único por rifa
            $table->unique(['raffle_id', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Remover a constraint unique de (raffle_id, number)
            $table->dropUnique(['raffle_id', 'number']);
            
            // Restaurar constraint unique de (user_id, number)
            $table->unique(['user_id', 'number']);
        });
    }
};
