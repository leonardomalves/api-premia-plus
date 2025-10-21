<?php

namespace Database\Factories;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /**
     * Tickets são apenas números no pool global
     * Não têm relacionamento direto com users ou raffles
     */
    public function definition(): array
    {
        return [
            'number' => str_pad(
                $this->faker->unique()->numberBetween(1, 9999999),
                7,
                '0',
                STR_PAD_LEFT
            ),
        ];
    }
}
