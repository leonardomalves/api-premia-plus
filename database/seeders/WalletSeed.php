<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Services\BusinessRules\WalletService;
use Illuminate\Database\Seeder;

class WalletSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('💰 Iniciando processamento de wallets...');

        $walletService = new WalletService();
        
        $approvedOrders = Order::where('status', 'approved')
            ->with(['plan', 'user'])
            ->get();

        if ($approvedOrders->isEmpty()) {
            $this->command->warn('⚠️ Nenhuma order aprovada encontrada');
            return;
        }

        $this->command->info("📊 Encontradas {$approvedOrders->count()} orders aprovadas para processar");

        $processed = 0;
        $failed = 0;
        $totalAmount = 0;

        foreach ($approvedOrders as $order) {
            $result = $walletService->processWallet($order);
            
            if ($result['success']) {
                $processed++;
                $totalAmount += $result['amount'] ?? 0;
                
                $userEmail = $order->user->email ?? 'N/A';
                $amount = number_format($result['amount'] ?? 0, 2, ',', '.');
                $newBalance = number_format($result['new_balance'] ?? 0, 2, ',', '.');
                
                $this->command->line("  ✅ Order {$order->uuid} → {$userEmail} | R$ {$amount} | Saldo: R$ {$newBalance}");
            } else {
                $failed++;
                $this->command->error("  ❌ Falha ao processar order {$order->uuid}: {$result['message']}");
            }
        }

        $this->command->info('');
        $this->command->info('📊 RESUMO DO PROCESSAMENTO DE WALLETS');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info("📦 Orders processadas: {$processed}");
        $this->command->info("❌ Falhas: {$failed}");
        $this->command->info("💰 Total creditado: R$ " . number_format($totalAmount, 2, ',', '.'));
        $this->command->info('═══════════════════════════════════════');
        
        $this->command->info('✅ Processamento de wallets concluído!');
    }
}
