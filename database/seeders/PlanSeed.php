<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlanSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Criando planos...');

        $plans = [
            [
                'name' => 'Plano Básico',
                'description' => 'Plano ideal para iniciantes no sistema de premiação',
                'price' => 99.90,
                'grant_tickets' => 1000,
                'status' => 'active',
                'commission_level_1' => 5.00,
                'commission_level_2' => 2.50,
                'commission_level_3' => 1.00,
                'is_promotional' => false,
                'overlap' => 0,
                'start_date' => now(),
                'end_date' => now()->addYear(),
            ],
            [
                'name' => 'Plano Premium',
                'description' => 'Plano intermediário com mais benefícios e comissões',
                'price' => 199.90,
                'grant_tickets' => 2500,
                'status' => 'active',
                'commission_level_1' => 8.00,
                'commission_level_2' => 4.00,
                'commission_level_3' => 2.00,
                'is_promotional' => false,
                'overlap' => 1,
                'start_date' => now(),
                'end_date' => now()->addYear(),
            ],
            [
                'name' => 'Plano VIP',
                'description' => 'Plano avançado com máximos benefícios e comissões',
                'price' => 399.90,
                'grant_tickets' => 5000,
                'status' => 'active',
                'commission_level_1' => 12.00,
                'commission_level_2' => 6.00,
                'commission_level_3' => 3.00,
                'is_promotional' => false,
                'overlap' => 2,
                'start_date' => now(),
                'end_date' => now()->addYear(),
            ],
            [
                'name' => 'Plano Promocional',
                'description' => 'Plano especial com desconto por tempo limitado',
                'price' => 149.90,
                'grant_tickets' => 3000,
                'status' => 'active',
                'commission_level_1' => 10.00,
                'commission_level_2' => 5.00,
                'commission_level_3' => 2.50,
                'is_promotional' => true,
                'overlap' => 1,
                'start_date' => now(),
                'end_date' => now()->addMonths(3),
            ],
        ];

        foreach ($plans as $planData) {
            $plan = Plan::create([
                'uuid' => Str::uuid(),
                ...$planData
            ]);

            $this->command->info("✅ Plano criado: {$plan->name} - R$ {$plan->price}");
        }

        $this->command->info('🎉 4 planos criados com sucesso!');
        $this->command->info('📊 Planos disponíveis:');
        $this->command->info('   • Plano Básico - R$ 99,90');
        $this->command->info('   • Plano Premium - R$ 199,90');
        $this->command->info('   • Plano VIP - R$ 399,90');
        $this->command->info('   • Plano Promocional - R$ 149,90');
    }
}
