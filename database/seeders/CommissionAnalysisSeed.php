<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CommissionAnalysisSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔍 Iniciando análise de comissões...');
        
        // Configurações
        $maxLevels = 3;
        $testPlanId = 1; // ID do plano para teste
        
        // Buscar plano de teste
        $plan = Plan::find($testPlanId);
        if (!$plan) {
            $this->command->error('❌ Plano não encontrado. Execute primeiro o PlanSeed.');
            return;
        }
        
        $this->command->info("📦 Plano de teste: {$plan->name} - R$ " . number_format($plan->price, 2, ',', '.'));
        $this->command->info("💰 Taxas de comissão: Nível 1: {$plan->commission_level_1}%, Nível 2: {$plan->commission_level_2}%, Nível 3: {$plan->commission_level_3}%");
        
        // Analisar estrutura de uplines
        $this->analyzeUplineStructure($maxLevels);
        
        // Simular compras e calcular comissões
        $this->simulateCommissions($plan, $maxLevels);
        
        $this->command->info('✅ Análise de comissões concluída!');
    }
    
    /**
     * Analisa a estrutura de uplines
     */
    private function analyzeUplineStructure(int $maxLevels): void
    {
        $this->command->info('🌳 Analisando estrutura de uplines...');
        
        $users = User::whereNotNull('sponsor_id')->get();
        $uplineStats = [];
        
        foreach ($users as $user) {
            $uplines = $this->getUplines($user, $maxLevels);
            $levelCount = count($uplines);
            
            if (!isset($uplineStats[$levelCount])) {
                $uplineStats[$levelCount] = 0;
            }
            $uplineStats[$levelCount]++;
        }
        
        $this->command->info('📊 Distribuição de uplines:');
        for ($i = 1; $i <= $maxLevels; $i++) {
            $count = $uplineStats[$i] ?? 0;
            $this->command->info("   - {$i} nível(is): {$count} usuários");
        }
    }
    
    /**
     * Simula comissões para diferentes cenários
     */
    private function simulateCommissions(Plan $plan, int $maxLevels): void
    {
        $this->command->info('🧪 Simulando cenários de comissão...');
        
        // Cenário 1: Usuário com 3 níveis de upline
        $this->simulateScenario($plan, 'Usuário com 3 níveis de upline', 3);
        
        // Cenário 2: Usuário com 2 níveis de upline
        $this->simulateScenario($plan, 'Usuário com 2 níveis de upline', 2);
        
        // Cenário 3: Usuário com 1 nível de upline
        $this->simulateScenario($plan, 'Usuário com 1 nível de upline', 1);
        
        // Cenário 4: Usuário sem upline
        $this->simulateScenario($plan, 'Usuário sem upline', 0);
    }
    
    /**
     * Simula um cenário específico
     */
    private function simulateScenario(Plan $plan, string $scenario, int $uplineLevels): void
    {
        $this->command->info("🎯 {$scenario}:");
        
        $totalCommissions = 0;
        
        for ($level = 1; $level <= $uplineLevels; $level++) {
            $rate = $this->getCommissionRate($plan, $level);
            $commission = $plan->price * ($rate / 100);
            $totalCommissions += $commission;
            
            $this->command->info("   💰 Nível {$level}: {$rate}% = R$ " . number_format($commission, 2, ',', '.'));
        }
        
        $netMargin = $plan->price - $totalCommissions;
        $marginPercentage = ($netMargin / $plan->price) * 100;
        
        $this->command->info("   📊 Total comissões: R$ " . number_format($totalCommissions, 2, ',', '.'));
        $this->command->info("   📈 Margem líquida: R$ " . number_format($netMargin, 2, ',', '.') . " ({$marginPercentage}%)");
        $this->command->info('');
    }
    
    /**
     * Busca uplines com profundidade configurável
     */
    private function getUplines(User $user, int $maxLevels): array
    {
        $uplines = [];
        $currentUser = $user;
        $level = 0;
        
        while ($level < $maxLevels && $currentUser->sponsor_id) {
            $sponsor = User::find($currentUser->sponsor_id);
            
            if (!$sponsor) {
                break;
            }
            
            $uplines[$level] = $sponsor;
            $currentUser = $sponsor;
            $level++;
        }
        
        return $uplines;
    }
    
    /**
     * Obtém taxa de comissão por nível
     */
    private function getCommissionRate(Plan $plan, int $level): float
    {
        return match($level) {
            1 => (float) $plan->commission_level_1,
            2 => (float) $plan->commission_level_2,
            3 => (float) $plan->commission_level_3,
            default => 0.0
        };
    }
    
    /**
     * Exibe resumo final
     */
    public function showSummary(): void
    {
        $this->command->info('📋 Resumo da Análise:');
        
        $totalUsers = User::count();
        $usersWithUplines = User::whereNotNull('sponsor_id')->count();
        $activePlans = Plan::where('status', 'active')->count();
        
        $this->command->info("   - Total de usuários: {$totalUsers}");
        $this->command->info("   - Usuários com uplines: {$usersWithUplines}");
        $this->command->info("   - Planos ativos: {$activePlans}");
        $this->command->info("   - Profundidade configurada: 3 níveis");
    }
}
