<?php

namespace Database\Seeders;

use App\Models\Plan;
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
                'price' => 19.90,
                'status' => 'active',
                'commission_level_1' => 5.00,
                'commission_level_2' => 4.50,
                'commission_level_3' => 3.00,
                'is_promotional' => false,
                'start_date' => now(),
                'end_date' => now()->addYear(),
            ],
            [
                'name' => 'Plano Premium',
                'description' => 'Plano intermediário com mais benefícios e comissões',
                'price' => 39.90,
                'status' => 'active',
                'commission_level_1' => 6.00,
                'commission_level_2' => 4.00,
                'commission_level_3' => 3.00,
                'is_promotional' => false,
                'start_date' => now(),
                'end_date' => now()->addYear(),
            ],
            [
                'name' => 'Plano VIP',
                'description' => 'Plano avançado com máximos benefícios e comissões',
                'price' => 49.90,
                'status' => 'active',
                'commission_level_1' => 7.00,
                'commission_level_2' => 4.00,
                'commission_level_3' => 3.00,
                'is_promotional' => false,
                'start_date' => now(),
                'end_date' => now()->addYear(),
            ],
            [
                'name' => 'Plano Promocional',
                'description' => 'Plano especial com desconto por tempo limitado',
                'price' => 99.90,
                'commission_level_1' => 8.00,
                'commission_level_2' => 4.00,
                'commission_level_3' => 3.00,
                'is_promotional' => true,
                'start_date' => now(),
                'end_date' => now()->addMonths(3),
            ],
        ];

        foreach ($plans as $planData) {
            $plan = Plan::create([
                'uuid' => Str::uuid(),
                ...$planData,
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
