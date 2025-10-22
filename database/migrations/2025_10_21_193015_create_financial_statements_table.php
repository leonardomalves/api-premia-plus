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
        Schema::create('financial_statements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('correlation_id'); // Removido unique - múltiplos usuários podem ter transações da mesma origem
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 15, 2)->default(0);
            $table->enum('type', ['credit', 'debit']);
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->enum('origin', ['user', 'system', 'plan', 'commission', 'balance', 'raffle'])->nullable();
            $table->string('description')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Chave composta: um usuário não pode ter duplicate transaction para o mesmo correlation_id, type e origin
            $table->unique(['user_id', 'correlation_id', 'type', 'origin'], 'financial_statements_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_statements');
    }
};
