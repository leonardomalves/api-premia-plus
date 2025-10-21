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
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Quem recebe a comissão
            $table->foreignId('origin_user_id')->constrained('users')->onDelete('cascade'); // Quem fez a compra
            $table->decimal('amount', 10, 2); // Valor da comissão
            $table->boolean('paid')->default(false); // Se foi pago
            $table->timestamp('available_at')->nullable(); // Quando fica disponível para saque
            $table->timestamps();

            // Índice único para evitar duplicação
            $table->unique(['order_id', 'user_id', 'origin_user_id'], 'unique_commission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
