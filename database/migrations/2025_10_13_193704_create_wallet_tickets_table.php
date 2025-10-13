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
        Schema::create('wallet_tickets', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')
            ->unique();


            $table->foreignId('user_id')
            ->constrained('users')
            ->onDelete('cascade');

            $table->foreignId('order_id')
            ->constrained('orders')
            ->onDelete('cascade');

            $table->foreignId('plan_id')
            ->constrained('plans')
            ->onDelete('cascade');

            $table->integer('ticket_level')
            ->default(1);

            $table->integer('total_tickets')
            ->default(0);

            $table->integer('total_tickets_used')
            ->default(0);

            $table->integer('bonus_tickets')
            ->default(0);

            $table->date('expiration_date')
            ->nullable();

            $table->enum('status', ['active', 'expired', 'blocked', 'pending'])->default('active');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_tickets');
    }
};
