<?php

namespace Database\Factories;

use App\Models\Raffle;
use App\Models\RaffleTicket;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RaffleTicket>
 */
class RaffleTicketFactory extends Factory
{
    protected $model = RaffleTicket::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'raffle_id' => Raffle::factory(),
            'ticket_id' => Ticket::factory(),
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'winner']),
        ];
    }

    /**
     * Indicate that the raffle ticket is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the raffle ticket is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }

    /**
     * Indicate that the raffle ticket is a winner.
     */
    public function winner(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'winner',
        ]);
    }
}
