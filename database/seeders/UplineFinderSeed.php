<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use App\Services\BusinessRules\UpLinesService;
use App\Services\BusinessRules\PayComission;
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
        
        $upLinesService = new UpLinesService();
        $payCommission = new PayComission();
        
        foreach ($orders as $order) {
            $this->command->info("🛒 Processando order: {$order->uuid} - Usuário: {$order->user->name}");
            
            // Buscar uplines
            $uplinesResult = $upLinesService->run($order);
            
            if ($uplinesResult['success']) {
                $this->command->info("   📊 Uplines encontrados: " . count($uplinesResult['uplines']));
                
                // Processar comissões
                $commissionResult = $payCommission->processOrderCommissions($order);
                
                if ($commissionResult['success']) {
                    $this->command->info("   ✅ Comissões processadas: {$commissionResult['commissions_created']} comissões, R$ " . number_format($commissionResult['total_amount'], 2, ',', '.'));
                } else {
                    $this->command->error("   ❌ Erro ao processar comissões: {$commissionResult['message']}");
                }
            } else {
                $this->command->warn("   ⚠️ Nenhum upline encontrado: {$uplinesResult['message']}");
            }
        }
        
        $this->command->info('✅ Processamento de orders concluído!');
    }
}
