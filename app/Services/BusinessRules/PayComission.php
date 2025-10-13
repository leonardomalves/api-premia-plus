<?php

namespace App\Services\BusinessRules;

use App\Models\Commission;
use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PayComission
{
    /**
     * Paga comissões para um usuário específico
     */
    public function payUserCommissions(string $userUuid): array
    {
        Log::info("💰 Iniciando pagamento de comissões para usuário: {$userUuid}");
        
        $user = User::where('uuid', $userUuid)->first();
        
        if (!$user) {
            Log::warning("⚠️ Usuário não encontrado: {$userUuid}");
            return [
                'success' => false,
                'message' => 'Usuário não encontrado',
                'commissions_paid' => 0,
                'total_amount' => 0
            ];
        }
        
        // Buscar comissões disponíveis para pagamento
        $availableCommissions = Commission::where('user_id', $user->id)
            ->where('available_at', '<=', now())
            ->where('paid', false)
            ->get();
            
        if ($availableCommissions->isEmpty()) {
            Log::info("ℹ️ Nenhuma comissão disponível para pagamento");
            return [
                'success' => true,
                'message' => 'Nenhuma comissão disponível para pagamento',
                'commissions_paid' => 0,
                'total_amount' => 0
            ];
        }
        
        Log::info("📊 Encontradas {$availableCommissions->count()} comissões disponíveis");
        
        // Processar pagamentos em transação
        return DB::transaction(function () use ($availableCommissions, $user) {
            $totalAmount = 0;
            $commissionsPaid = 0;
            
            foreach ($availableCommissions as $commission) {
                $result = $this->processCommissionPayment($commission);
                
                if ($result['success']) {
                    $totalAmount += $commission->amount;
                    $commissionsPaid++;
                    Log::info("✅ Comissão paga: R$ " . number_format($commission->amount, 2, ',', '.'));
                } else {
                    Log::error("❌ Erro ao pagar comissão ID {$commission->id}: {$result['message']}");
                }
            }
            
            Log::info("💰 Pagamento concluído: {$commissionsPaid} comissões, R$ " . number_format($totalAmount, 2, ',', '.'));
            
            return [
                'success' => true,
                'message' => 'Pagamento processado com sucesso',
                'commissions_paid' => $commissionsPaid,
                'total_amount' => $totalAmount,
                'user' => $user
            ];
        });
    }
    
    /**
     * Paga todas as comissões disponíveis
     */
    public function payAllAvailableCommissions(): array
    {
        Log::info('💰 Iniciando pagamento de todas as comissões disponíveis...');
        
        // Buscar todas as comissões disponíveis
        $availableCommissions = Commission::where('available_at', '<=', now())
            ->where('paid', false)
            ->get();
            
        if ($availableCommissions->isEmpty()) {
            Log::info('ℹ️ Nenhuma comissão disponível para pagamento');
            return [
                'success' => true,
                'message' => 'Nenhuma comissão disponível para pagamento',
                'commissions_paid' => 0,
                'total_amount' => 0
            ];
        }
        
        Log::info("📊 Encontradas {$availableCommissions->count()} comissões disponíveis");
        
        // Agrupar por usuário
        $commissionsByUser = $availableCommissions->groupBy('user_id');
        
        $totalCommissionsPaid = 0;
        $totalAmount = 0;
        $usersProcessed = [];
        
        foreach ($commissionsByUser as $userId => $userCommissions) {
            $user = User::find($userId);
            $result = $this->payUserCommissions($user->uuid);
            
            if ($result['success']) {
                $totalCommissionsPaid += $result['commissions_paid'];
                $totalAmount += $result['total_amount'];
                $usersProcessed[] = $user;
            }
        }
        
        Log::info("💰 Pagamento global concluído: {$totalCommissionsPaid} comissões, R$ " . number_format($totalAmount, 2, ',', '.'));
        
        return [
            'success' => true,
            'message' => 'Pagamento global processado com sucesso',
            'commissions_paid' => $totalCommissionsPaid,
            'total_amount' => $totalAmount,
            'users_processed' => count($usersProcessed)
        ];
    }
    
    /**
     * Processa o pagamento de uma comissão específica
     */
    private function processCommissionPayment(Commission $commission): array
    {
        try {
            // Verificar se a comissão já foi paga
            if ($commission->paid) {
                return [
                    'success' => false,
                    'message' => 'Comissão já foi paga'
                ];
            }
            
            // Verificar se está disponível para pagamento
            if ($commission->available_at > now()) {
                return [
                    'success' => false,
                    'message' => 'Comissão ainda não está disponível para pagamento'
                ];
            }
            
            // Aqui você implementaria a lógica de pagamento real
            // Por exemplo: integração com gateway de pagamento, transferência bancária, etc.
            $this->executePayment($commission);
            
            // Marcar como paga
            $commission->update(['paid' => true]);
            
            return [
                'success' => true,
                'message' => 'Comissão paga com sucesso'
            ];
            
        } catch (\Exception $e) {
            Log::error("❌ Erro ao processar pagamento da comissão ID {$commission->id}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao processar pagamento: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Executa o pagamento real (implementar conforme necessário)
     */
    private function executePayment(Commission $commission): void
    {
        // TODO: Implementar lógica de pagamento real
        // Exemplos:
        // - Integração com gateway de pagamento
        // - Transferência bancária
        // - Adicionar saldo na carteira do usuário
        // - Enviar para sistema de pagamentos externo
        
        Log::info("💳 Executando pagamento de R$ " . number_format($commission->amount, 2, ',', '.') . " para usuário ID {$commission->user_id}");
        
        // Por enquanto, apenas simula o pagamento
        // sleep(1); // Simula processamento
    }
    
    /**
     * Exibe estatísticas de comissões
     */
    public function showStatistics(): array
    {
        $totalCommissions = Commission::count();
        $totalAmount = Commission::sum('amount');
        $paidCommissions = Commission::where('paid', true)->count();
        $availableCommissions = Commission::where('available_at', '<=', now())->where('paid', false)->count();
        $pendingCommissions = Commission::where('available_at', '>', now())->where('paid', false)->count();
        
        Log::info('📊 Estatísticas de Comissões:');
        Log::info("   - Total de comissões: {$totalCommissions}");
        Log::info("   - Valor total: R$ " . number_format($totalAmount, 2, ',', '.'));
        Log::info("   - Comissões pagas: {$paidCommissions}");
        Log::info("   - Comissões disponíveis: {$availableCommissions}");
        Log::info("   - Comissões pendentes: {$pendingCommissions}");
        
        return [
            'total_commissions' => $totalCommissions,
            'total_amount' => $totalAmount,
            'paid_commissions' => $paidCommissions,
            'available_commissions' => $availableCommissions,
            'pending_commissions' => $pendingCommissions
        ];
    }
    
    /**
     * Busca comissões de um usuário
     */
    public function getUserCommissions(string $userUuid): array
    {
        $user = User::where('uuid', $userUuid)->first();
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Usuário não encontrado'
            ];
        }
        
        $commissions = Commission::where('user_id', $user->id)
            ->with(['order', 'originUser'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        return [
            'success' => true,
            'user' => $user,
            'commissions' => $commissions,
            'total_amount' => $commissions->sum('amount'),
            'available_amount' => $commissions->where('available_at', '<=', now())->where('paid', false)->sum('amount')
        ];
    }
}
