<?php

namespace Database\Factories;

use App\Models\Commission;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommissionFactory extends Factory
{
    protected $model = Commission::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'amount' => $this->faker->randomFloat(2, 5, 100),
            'level' => $this->faker->numberBetween(1, 3),
            'status' => $this->faker->randomElement(['pending', 'paid']),
        ];
    }

    public function paid(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'paid',
            ];
        });
    }
}
