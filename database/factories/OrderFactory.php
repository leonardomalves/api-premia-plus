<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'user_id' => User::factory(),
            'user_metadata' => [
                'name' => $this->faker->name(),
                'email' => $this->faker->email(),
                'phone' => $this->faker->phoneNumber(),
            ],
            'plan_id' => Plan::factory(),
            'plan_metadata' => [
                'name' => $this->faker->words(3, true),
                'description' => $this->faker->sentence(),
                'price' => $this->faker->randomFloat(2, 10, 500),
                'commission_level_1' => $this->faker->randomFloat(2, 1, 15),
                'commission_level_2' => $this->faker->randomFloat(2, 1, 10),
                'commission_level_3' => $this->faker->randomFloat(2, 1, 5),
                'is_promotional' => $this->faker->boolean(),
            ],
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected', 'cancelled']),
        ];
    }

    public function approved(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'approved',
            ];
        });
    }
}