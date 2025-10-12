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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->integer('grant_tickets');

            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');

            $table->decimal('commission_level_1', 5, 2)->default(10.00);
            $table->decimal('commission_level_2', 5, 2)->default(5.00);
            $table->decimal('commission_level_3', 5, 2)->default(2.00);

            $table->boolean('is_promotional')
            ->default(false);

            $table->integer('overlap');

            $table->date('start_date');
            $table->date('end_date');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
