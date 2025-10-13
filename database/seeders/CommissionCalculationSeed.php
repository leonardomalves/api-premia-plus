<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Order;
use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CommissionCalculationSeed extends Seeder
{
    /**
     * Profundidade configurável para buscar uplines
     */
    private $maxLevels = 3;
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('💰 Iniciando cálculo de comissões...');
        
        // Buscar todas as orders aprovadas
        $orders = Order::where('status', 'approved')
            ->with(['user', 'plan'])
            ->get();
            
        if ($orders->isEmpty()) {
            $this->command->warn('⚠️ Nenhuma order aprovada encontrada. Execute primeiro o seeder de orders.');
            return;
        }
        
        $this->command->info("📊 Processando {$orders->count()} orders aprovadas...");
        
        foreach ($orders as $order) {
            $this->calculateCommissions($order);
        }
        
        $this->command->info('✅ Cálculo de comissões concluído!');
    }
    
    /**
     * Calcula comissões para uma order específica
     */
    private function calculateCommissions(Order $order): void
    {
        $buyer = $order->user;
        $plan = $order->plan;
        
        $this->command->info("🛒 Processando order do usuário: {$buyer->name} (Plano: {$plan->name})");
        
        // Buscar uplines até o nível configurado
        $uplines = $this->getUplines($buyer, $this->maxLevels);
        
        if (empty($uplines)) {
            $this->command->warn("   ⚠️ Nenhum upline encontrado para {$buyer->name}");
            return;
        }
        
        // Calcular comissões para cada nível
        foreach ($uplines as $level => $upline) {
            $this->calculateLevelCommission($order, $upline, $level + 1);
        }
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
     * Calcula comissão para um nível específico
     */
    private function calculateLevelCommission(Order $order, User $upline, int $level): void
    {
        $plan = $order->plan;
        $commissionRate = $this->getCommissionRate($plan, $level);
        
        if ($commissionRate <= 0) {
            $this->command->warn("   ⚠️ Taxa de comissão zero para nível {$level}");
            return;
        }
        
        $commissionAmount = $plan->price * ($commissionRate / 100);
        
        $this->command->info("   💰 Nível {$level}: {$upline->name} - R$ " . number_format($commissionAmount, 2, ',', '.') . " ({$commissionRate}%)");
        
        // Aqui você pode salvar a comissão no banco de dados
        // Exemplo: Commission::create([...])
        $this->saveCommission($order, $upline, $level, $commissionAmount, $commissionRate);
    }
    
    /**
     * Obtém a taxa de comissão baseada no plano e nível
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
     * Salva a comissão calculada
     */
    private function saveCommission(Order $order, User $upline, int $level, float $amount, float $rate): void
    {
        // Por enquanto, apenas exibe a comissão
        // Você pode implementar a lógica de salvamento aqui
        $this->command->info("   📝 Comissão calculada: {$upline->name} - Nível {$level} - R$ " . number_format($amount, 2, ',', '.'));
    }
    
    /**
     * Configura a profundidade máxima de uplines
     */
    public function setMaxLevels(int $levels): self
    {
        $this->maxLevels = $levels;
        return $this;
    }
    
    /**
     * Exibe estatísticas das comissões
     */
    public function showStatistics(): void
    {
        $this->command->info('📈 Estatísticas de Comissões:');
        $this->command->info("   - Profundidade configurada: {$this->maxLevels} níveis");
        $this->command->info("   - Orders processadas: " . Order::where('status', 'approved')->count());
        $this->command->info("   - Usuários com uplines: " . User::whereNotNull('sponsor_id')->count());
    }
}
