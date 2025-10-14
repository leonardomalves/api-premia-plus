<?php

namespace Database\Factories;

use App\Models\WalletTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WalletTicketFactory extends Factory
{
    protected $model = WalletTicket::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'transaction_type' => $this->faker->randomElement(['deposit', 'withdrawal', 'transfer']),
            'amount' => $this->faker->randomFloat(2, 1, 100),
            'balance_before' => $this->faker->randomFloat(2, 0, 1000),
            'balance_after' => function (array $attributes) {
                $before = $attributes['balance_before'];
                $amount = $attributes['amount'];
                
                return match ($attributes['transaction_type']) {
                    'deposit' => $before + $amount,
                    'withdrawal' => max(0, $before - $amount),
                    'transfer' => $before, // Neutral for this user
                    default => $before,
                };
            },
            'metadata' => [
                'description' => $this->faker->sentence(),
                'processed_at' => now()->toISOString(),
            ],
        ];
    }

    public function deposit(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'transaction_type' => 'deposit',
            ];
        });
    }
}