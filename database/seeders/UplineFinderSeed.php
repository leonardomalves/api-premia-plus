<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Services\BusinessRules\PayCommissionService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UplineFinderSeed extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔍 Iniciando processamento de orders aprovadas...');
        
        // Buscar orders aprovadas
        $orders = Order::where('status', 'approved')
            ->with('user')
            ->get();
            
        if ($orders->isEmpty()) {
            $this->command->warn('⚠️ Nenhuma order aprovada encontrada.');
            return;
        }
        
        $this->command->info("📊 Encontradas {$orders->count()} orders aprovadas");
        
        $payCommission = new PayCommissionService();
        
        foreach ($orders as $order) {
            $this->command->info("🛒 Processando order: {$order->uuid} - Usuário: {$order->user->name}");
            
            // PayComission já faz tudo: busca uplines + processa comissões
            $result = $payCommission->processOrderCommissions($order);
            
            if ($result['success']) {
                $this->command->info("   ✅ Comissões processadas: {$result['commissions_created']} comissões, R$ " . number_format($result['total_amount'], 2, ',', '.'));
            } else {
                $this->command->warn("   ⚠️ {$result['message']}");
            }
        }
        
        $this->command->info('✅ Processamento de orders concluído!');
    }
}
