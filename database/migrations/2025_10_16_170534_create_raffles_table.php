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
        Schema::create('raffles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('prize_value', 10, 2);
            $table->decimal('operation_cost', 10, 2);
            $table->decimal('unit_ticket_value', 10, 2);
            $table->decimal('liquidity_ratio', 5, 2)->default(0);
            $table->decimal('liquid_value', 10, 2);
            $table->integer('min_tickets_required')->default(5);
            $table->date('draw_date')->nullable();            
            $table->enum('status', ['pending', 'active', 'completed', 'cancelled', 'inactive']);            
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('winner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('winner_ticket')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raffles');
    }
};
