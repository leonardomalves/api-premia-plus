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
            // Drop the old global unique constraint on number
            $table->dropUnique(['number']);
            
            // Add raffle_id column
            $table->foreignId('raffle_id')->nullable()->after('user_id')->constrained('orders')->onDelete('cascade');
            
            // Add composite unique constraint: same user_id cannot have duplicate number
            $table->unique(['user_id', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Drop the composite unique constraint
            $table->dropUnique(['user_id', 'number']);
            
            // Drop raffle_id foreign key and column
            $table->dropForeign(['raffle_id']);
            $table->dropColumn('raffle_id');
            
            // Restore the global unique constraint
            $table->unique('number');
        });
    }
};
