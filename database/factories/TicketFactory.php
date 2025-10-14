<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'user_id' => User::factory(),
            'ticket_level' => $this->faker->numberBetween(1, 3),
            'granted_by_order_id' => null, // Will be set if needed
            'metadata' => [
                'source' => $this->faker->randomElement(['order', 'bonus']),
                'generated_at' => now()->toISOString(),
            ],
        ];
    }

    public function levelOne(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'ticket_level' => 1,
            ];
        });
    }
}