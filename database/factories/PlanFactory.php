<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'grant_tickets' => $this->faker->numberBetween(1, 20),
            'ticket_level' => $this->faker->numberBetween(1, 3),
            'commission_level_1' => $this->faker->randomFloat(2, 1, 15),
            'commission_level_2' => $this->faker->randomFloat(2, 1, 10),
            'commission_level_3' => $this->faker->randomFloat(2, 1, 5),
            'is_promotional' => $this->faker->boolean(),
            'max_users' => $this->faker->numberBetween(0, 1000),
            'overlap' => $this->faker->numberBetween(1, 10),
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->date(),
            'status' => $this->faker->randomElement(['active', 'inactive', 'archived']),
        ];
    }

    public function active(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'active',
            ];
        });
    }

    public function promotional(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_promotional' => true,
            ];
        });
    }
}