<?php

namespace Database\Seeders;

use App\Services\BusinessRules\PayComission;
use App\Services\BusinessRules\UpLinesService;
use App\Models\User;
use App\Models\Commission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PayCommissionTestSeed extends Seeder
{


    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('💰 Iniciando teste do sistema de pagamento de comissões...');
        
        // Instanciar serviços
        $payCommission = new PayComission();
        $upLinesService = new UpLinesService();
        
        // Mostrar estatísticas iniciais
        $this->command->info('📊 Estatísticas iniciais:');
        $this->showInitialStatistics();
        
        // Testar pagamento para usuário específico
        $this->testUserPayment($payCommission);
        
        // Testar pagamento global
        $this->testGlobalPayment($payCommission);
        
        // Mostrar estatísticas finais
        $this->command->info('📊 Estatísticas finais:');
        $this->showFinalStatistics($payCommission);
        
        $this->command->info('✅ Teste do sistema de pagamento concluído!');
    }
    
    /**
     * Testa pagamento para usuário específico
     */
    private function testUserPayment(PayComission $payCommission): void
    {
        $this->command->info('🧪 Testando pagamento para usuário específico...');
        
        // Buscar usuário com comissões
        $user = User::whereHas('commissions')->first();
        
        if (!$user) {
            $this->command->warn('⚠️ Nenhum usuário com comissões encontrado');
            return;
        }
        
        $this->command->info("👤 Testando pagamento para: {$user->name} (UUID: {$user->uuid})");
        
        // Buscar comissões do usuário
        $userCommissions = $payCommission->getUserCommissions($user->uuid);
        
        if ($userCommissions['success']) {
            $this->command->info("📊 Comissões encontradas: {$userCommissions['commissions']->count()}");
            $this->command->info("💰 Valor total: R$ " . number_format($userCommissions['total_amount'], 2, ',', '.'));
            $this->command->info("💵 Valor disponível: R$ " . number_format($userCommissions['available_amount'], 2, ',', '.'));
        }
        
        // Processar pagamento
        $result = $payCommission->payUserCommissions($user->uuid);
        
        if ($result['success']) {
            $this->command->info("✅ Pagamento processado: {$result['commissions_paid']} comissões, R$ " . number_format($result['total_amount'], 2, ',', '.'));
        } else {
            $this->command->error("❌ Erro no pagamento: {$result['message']}");
        }
    }
    
    /**
     * Testa pagamento global
     */
    private function testGlobalPayment(PayComission $payCommission): void
    {
        $this->command->info('🌍 Testando pagamento global...');
        
        $result = $payCommission->payAllAvailableCommissions();
        
        if ($result['success']) {
            $this->command->info("✅ Pagamento global processado: {$result['commissions_paid']} comissões, R$ " . number_format($result['total_amount'], 2, ',', '.'));
            $this->command->info("👥 Usuários processados: {$result['users_processed']}");
        } else {
            $this->command->error("❌ Erro no pagamento global: {$result['message']}");
        }
    }
    
    /**
     * Mostra estatísticas iniciais
     */
    private function showInitialStatistics(): void
    {
        $totalCommissions = Commission::count();
        $totalAmount = Commission::sum('amount');
        $paidCommissions = Commission::where('paid', true)->count();
        $availableCommissions = Commission::where('available_at', '<=', now())->where('paid', false)->count();
        
        $this->command->info("   - Total de comissões: {$totalCommissions}");
        $this->command->info("   - Valor total: R$ " . number_format($totalAmount, 2, ',', '.'));
        $this->command->info("   - Comissões pagas: {$paidCommissions}");
        $this->command->info("   - Comissões disponíveis: {$availableCommissions}");
    }
    
    /**
     * Mostra estatísticas finais
     */
    private function showFinalStatistics(PayComission $payCommission): void
    {
        $stats = $payCommission->showStatistics();
        
        $this->command->info("   - Total de comissões: {$stats['total_commissions']}");
        $this->command->info("   - Valor total: R$ " . number_format($stats['total_amount'], 2, ',', '.'));
        $this->command->info("   - Comissões pagas: {$stats['paid_commissions']}");
        $this->command->info("   - Comissões disponíveis: {$stats['available_commissions']}");
        $this->command->info("   - Comissões pendentes: {$stats['pending_commissions']}");
    }
}
