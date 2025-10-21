<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommissionTestSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🧪 Iniciando teste de cálculo de comissões...');

        // Configurar profundidade (pode ser alterada)
        $maxLevels = 3;

        // Buscar usuários com uplines
        $usersWithUplines = User::whereNotNull('sponsor_id')
            ->with(['sponsor', 'sponsor.sponsor', 'sponsor.sponsor.sponsor'])
            ->get();

        if ($usersWithUplines->isEmpty()) {
            $this->command->warn('⚠️ Nenhum usuário com upline encontrado. Execute primeiro o CreateUserSeed.');

            return;
        }

        $this->command->info("👥 Encontrados {$usersWithUplines->count()} usuários com uplines");

        // Simular uma compra para cada usuário
        $plans = Plan::where('status', 'active')->get();

        if ($plans->isEmpty()) {
            $this->command->warn('⚠️ Nenhum plano ativo encontrado. Execute primeiro o PlanSeed.');

            return;
        }

        foreach ($usersWithUplines as $user) {
            $this->simulatePurchaseAndCalculateCommissions($user, $plans->random(), $maxLevels);
        }

        $this->command->info('✅ Teste de comissões concluído!');
    }

    /**
     * Simula uma compra e calcula comissões
     */
    private function simulatePurchaseAndCalculateCommissions(User $buyer, Plan $plan, int $maxLevels): void
    {
        $this->command->info("🛒 Simulando compra: {$buyer->name} - Plano: {$plan->name} (R$ ".number_format($plan->price, 2, ',', '.').')');

        // Buscar uplines com profundidade configurável
        $uplines = $this->getUplinesRecursive($buyer, $maxLevels);

        if (empty($uplines)) {
            $this->command->warn("   ⚠️ Nenhum upline encontrado para {$buyer->name}");

            return;
        }

        $this->command->info('   📊 Uplines encontrados: '.count($uplines));

        // Calcular comissões para cada nível
        $totalCommissions = 0;
        foreach ($uplines as $level => $upline) {
            $commission = $this->calculateCommission($plan, $level + 1);
            $totalCommissions += $commission;

            $this->command->info('   💰 Nível '.($level + 1).": {$upline->name} - R$ ".number_format($commission, 2, ',', '.'));
        }

        $this->command->info('   📈 Total de comissões: R$ '.number_format($totalCommissions, 2, ',', '.'));
        $this->command->info('   📊 Margem líquida: R$ '.number_format($plan->price - $totalCommissions, 2, ',', '.'));
    }

    /**
     * Busca uplines recursivamente com profundidade configurável
     */
    private function getUplinesRecursive(User $user, int $maxLevels, int $currentLevel = 0): array
    {
        $uplines = [];

        if ($currentLevel >= $maxLevels || ! $user->sponsor_id) {
            return $uplines;
        }

        $sponsor = User::find($user->sponsor_id);

        if (! $sponsor) {
            return $uplines;
        }

        $uplines[$currentLevel] = $sponsor;

        // Buscar uplines do patrocinador (recursivo)
        $sponsorUplines = $this->getUplinesRecursive($sponsor, $maxLevels, $currentLevel + 1);

        return array_merge($uplines, $sponsorUplines);
    }

    /**
     * Calcula comissão baseada no plano e nível
     */
    private function calculateCommission(Plan $plan, int $level): float
    {
        $rate = match ($level) {
            1 => (float) $plan->commission_level_1,
            2 => (float) $plan->commission_level_2,
            3 => (float) $plan->commission_level_3,
            default => 0.0
        };

        return $plan->price * ($rate / 100);
    }

    /**
     * Exibe estatísticas do teste
     */
    public function showTestStatistics(): void
    {
        $this->command->info('📊 Estatísticas do Teste:');

        $usersWithUplines = User::whereNotNull('sponsor_id')->count();
        $totalUsers = User::count();
        $plans = Plan::where('status', 'active')->count();

        $this->command->info("   - Total de usuários: {$totalUsers}");
        $this->command->info("   - Usuários com uplines: {$usersWithUplines}");
        $this->command->info("   - Planos ativos: {$plans}");

        // Mostrar estrutura de uplines
        $this->showUplineStructure();
    }

    /**
     * Exibe a estrutura de uplines
     */
    private function showUplineStructure(): void
    {
        $this->command->info('🌳 Estrutura de Uplines:');

        $users = User::whereNotNull('sponsor_id')
            ->with(['sponsor', 'sponsor.sponsor', 'sponsor.sponsor.sponsor'])
            ->limit(5)
            ->get();

        foreach ($users as $user) {
            $this->command->info("   👤 {$user->name} (ID: {$user->id})");

            $current = $user;
            $level = 1;

            while ($current->sponsor_id && $level <= 3) {
                $sponsor = User::find($current->sponsor_id);
                if ($sponsor) {
                    $this->command->info("      ↳ Nível {$level}: {$sponsor->name} (ID: {$sponsor->id})");
                    $current = $sponsor;
                    $level++;
                } else {
                    break;
                }
            }
        }
    }
}
