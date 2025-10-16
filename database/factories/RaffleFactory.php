<?php

namespace Database\Factories;

use App\Models\Raffle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Raffle>
 */
class RaffleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Raffle::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $products = [
            [
                'title' => 'iPhone 15 Pro Max 512GB',
                'description' => 'O mais recente iPhone da Apple com 512GB de armazenamento, tela ProMotion de 6,7 polegadas e sistema de câmeras Pro avançado.',
                'prize_value' => 8999.99,
                'operation_cost' => 500.00,
                'unit_ticket_value' => 25.00,
                'tickets_required' => 400,
            ],
            [
                'title' => 'PlayStation 5 Digital Edition',
                'description' => 'Console de videogame mais avançado da Sony, versão digital com SSD ultra-rápido e gráficos em 4K.',
                'prize_value' => 3999.99,
                'operation_cost' => 300.00,
                'unit_ticket_value' => 15.00,
                'tickets_required' => 300,
            ],
            [
                'title' => 'MacBook Pro M3 14" 512GB',
                'description' => 'Laptop profissional da Apple com chip M3, tela Liquid Retina XDR de 14 polegadas e 512GB de SSD.',
                'prize_value' => 12999.99,
                'operation_cost' => 800.00,
                'unit_ticket_value' => 35.00,
                'tickets_required' => 400,
            ],
            [
                'title' => 'Samsung Galaxy S24 Ultra 256GB',
                'description' => 'Smartphone premium da Samsung com S Pen integrada, câmeras de alta resolução e tela Dynamic AMOLED de 6,8 polegadas.',
                'prize_value' => 6999.99,
                'operation_cost' => 400.00,
                'unit_ticket_value' => 20.00,
                'tickets_required' => 350,
            ],
            [
                'title' => 'Vale Compras R$ 5.000',
                'description' => 'Vale compras de R$ 5.000 para usar em lojas participantes, válido por 12 meses a partir da entrega.',
                'prize_value' => 5000.00,
                'operation_cost' => 250.00,
                'unit_ticket_value' => 18.00,
                'tickets_required' => 280,
            ],
            [
                'title' => 'Nintendo Switch OLED',
                'description' => 'Console portátil da Nintendo com tela OLED de 7 polegadas, 64GB de armazenamento interno e dock para TV.',
                'prize_value' => 2499.99,
                'operation_cost' => 150.00,
                'unit_ticket_value' => 12.00,
                'tickets_required' => 210,
            ]
        ];

        $product = $this->faker->randomElement($products);

        return [
            'uuid' => Str::uuid(),
            'title' => $product['title'],
            'description' => $product['description'],
            'prize_value' => $product['prize_value'],
            'operation_cost' => $product['operation_cost'],
            'unit_ticket_value' => $product['unit_ticket_value'],
            'liquidity_ratio' => $this->faker->numberBetween(60, 95),
            'liquid_value' => $product['prize_value'] * 0.8, // 80% do valor do prêmio como padrão
            'tickets_required' => $product['tickets_required'],
            'min_ticket_level' => $this->faker->numberBetween(1, 5),
            'max_tickets_per_user' => $this->faker->numberBetween(5, 20),
            'status' => $this->faker->randomElement(['pending', 'active', 'inactive', 'cancelled']),
            'notes' => $this->faker->optional(0.7)->paragraph(),
            'created_by' => User::factory(),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the raffle is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the raffle is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the raffle is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the raffle is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Create a high-value raffle.
     */
    public function highValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'prize_value' => $this->faker->numberBetween(10000, 50000),
            'operation_cost' => $this->faker->numberBetween(500, 2000),
            'unit_ticket_value' => $this->faker->numberBetween(30, 100),
            'tickets_required' => $this->faker->numberBetween(300, 800),
        ]);
    }

    /**
     * Create a low-value raffle.
     */
    public function lowValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'prize_value' => $this->faker->numberBetween(100, 1000),
            'operation_cost' => $this->faker->numberBetween(10, 100),
            'unit_ticket_value' => $this->faker->numberBetween(5, 20),
            'tickets_required' => $this->faker->numberBetween(50, 200),
        ]);
    }

    /**
     * Create a raffle with specific creator.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }

    /**
     * Create a raffle with custom prize value.
     */
    public function withPrizeValue(float $value): static
    {
        return $this->state(fn (array $attributes) => [
            'prize_value' => $value,
        ]);
    }

    /**
     * Create a raffle with custom title.
     */
    public function withTitle(string $title): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => $title,
        ]);
    }
}