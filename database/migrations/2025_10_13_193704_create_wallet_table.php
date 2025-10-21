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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')
            ->unique();


            $table->foreignId('user_id')
            ->constrained('users')
            ->onDelete('cascade');

            $table->decimal('commissions_for_this_month', 10, 2)
            ->default(0);   

            $table->decimal('total_commissions', 10, 2)
            ->default(0); 

            $table->decimal('balance', 10, 2)
            ->default(0);   

            $table->decimal('withdrawals', 10, 2)
            ->default(0);

            $table->decimal('blocked', 10, 2)
            ->default(0);

            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
