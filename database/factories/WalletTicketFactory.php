<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Models\WalletTicket;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WalletTicketFactory extends Factory
{
    protected $model = WalletTicket::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'user_id' => User::factory(),
            'order_id' => Order::factory(),
            'plan_id' => Plan::factory(),
            'ticket_level' => $this->faker->numberBetween(1, 5),
            'total_tickets' => $this->faker->numberBetween(1, 100),
            'status' => $this->faker->randomElement(['active', 'expired', 'used']),
            'expiration_date' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
        ];
    }

    /**
     * Indicate that the wallet ticket is active.
     */
    public function active(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'active',
            ];
        });
    }

    /**
     * Indicate that the wallet ticket is expired.
     */
    public function expired(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'expired',
                'expiration_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            ];
        });
    }
}
