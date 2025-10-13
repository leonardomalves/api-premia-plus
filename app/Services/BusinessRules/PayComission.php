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
     * Processa comissões para uma order específica
     */
    public function processOrderCommissions(Order $order): array
    {
        Log::info("💰 Processando comissões para order: {$order->uuid}");
        
        if ($order->status !== 'approved') {
            Log::warning("⚠️ Order não está aprovada: {$order->status}");
            return [
                'success' => false,
                'message' => 'Order não está aprovada',
                'order' => $order,
                'commissions_created' => 0,
                'total_amount' => 0
            ];
        }
        
        $user = $order->user;
        if (!$user) {
            Log::warning('⚠️ Usuário não encontrado na order');
            return [
                'success' => false,
                'message' => 'Usuário não encontrado na order',
                'order' => $order,
                'commissions_created' => 0,
                'total_amount' => 0
            ];
        }
        
        // Buscar uplines usando UpLinesService
        $upLinesService = new UpLinesService();
        $uplinesResult = $upLinesService->run($order);
        
        if (!$uplinesResult['success'] || empty($uplinesResult['uplines'])) {
            Log::info("ℹ️ Nenhum upline encontrado para order {$order->uuid}");
            return [
                'success' => true,
                'message' => 'Nenhum upline encontrado',
                'order' => $order,
                'commissions_created' => 0,
                'total_amount' => 0
            ];
        }
        
        Log::info("📊 Encontrados " . count($uplinesResult['uplines']) . " uplines para processar");
        
        // Processar comissões em transação
        return DB::transaction(function () use ($order, $uplinesResult) {
            $totalAmount = 0;
            $commissionsCreated = 0;
            $planMetadata = $order->plan_metadata;
            
            foreach ($uplinesResult['uplines'] as $uplineData) {
                $upline = User::find($uplineData['id']);
                $level = $uplineData['level'];
                
                // Criar comissão
                $result = $this->createCommission($order, $upline, $level, $planMetadata);
                
                if ($result['success']) {
                    $totalAmount += $result['amount'];
                    $commissionsCreated++;
                    Log::info("✅ Comissão criada: {$upline->name} - Nível {$level} - R$ " . number_format($result['amount'], 2, ',', '.'));
                    
                    // PAGAR IMEDIATAMENTE
                    $paymentResult = $this->processCommissionPayment($result['commission']);
                    
                    if ($paymentResult['success']) {
                        Log::info("💰 PAGO IMEDIATAMENTE: {$upline->name} - R$ " . number_format($result['amount'], 2, ',', '.'));
                    } else {
                        Log::error("❌ Erro ao pagar comissão: {$paymentResult['message']}");
                    }
                } else {
                    Log::error("❌ Erro ao criar comissão: {$result['message']}");
                }
            }
            
            Log::info("💰 Processamento concluído: {$commissionsCreated} comissões, R$ " . number_format($totalAmount, 2, ',', '.'));
            
            return [
                'success' => true,
                'message' => 'Comissões processadas com sucesso',
                'order' => $order,
                'commissions_created' => $commissionsCreated,
                'total_amount' => $totalAmount
            ];
        });
    }

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
     * Cria uma comissão para um upline específico
     */
    private function createCommission(Order $order, User $upline, int $level, array $planMetadata): array
    {
        try {
            // Calcular taxa de comissão baseada no nível
            $commissionRate = $this->getCommissionRateFromMetadata($planMetadata, $level);
            
            if ($commissionRate <= 0) {
                return [
                    'success' => false,
                    'message' => "Taxa de comissão zero para nível {$level}",
                    'amount' => 0
                ];
            }
            
            $planPrice = (float) $planMetadata['price'];
            $commissionAmount = $planPrice * ($commissionRate / 100);
            
            // Usar updateOrCreate para evitar duplicação
            $commission = Commission::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'user_id' => $upline->id,
                    'origin_user_id' => $order->user_id,
                ],
                [
                    'amount' => $commissionAmount,
                    'available_at' => now(), // Disponível imediatamente para pagamento
                ]
            );
            
            return [
                'success' => true,
                'message' => 'Comissão criada/atualizada com sucesso',
                'amount' => $commissionAmount,
                'commission' => $commission
            ];
            
        } catch (\Exception $e) {
            Log::error("❌ Erro ao criar comissão: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro ao criar comissão: ' . $e->getMessage(),
                'amount' => 0
            ];
        }
    }
    
    /**
     * Obtém taxa de comissão dos metadados do plano
     */
    private function getCommissionRateFromMetadata(array $planMetadata, int $level): float
    {
        return match($level) {
            1 => (float) $planMetadata['commission_level_1'],
            2 => (float) $planMetadata['commission_level_2'],
            3 => (float) $planMetadata['commission_level_3'],
            default => 0.0
        };
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
