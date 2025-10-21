<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProcessCartStatusSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🛍️ Iniciando processamento de carrinhos e orders...');

        // Buscar todos os carrinhos ativos
        $activeCarts = Cart::where('status', 'active')
            ->with(['user', 'plan'])
            ->get();

        if ($activeCarts->isEmpty()) {
            $this->command->warn('⚠️ Nenhum carrinho ativo encontrado');
            return;
        }

        $this->command->info("📊 Encontrados {$activeCarts->count()} carrinhos ativos para processar");

        $completed = 0;
        $abandoned = 0;
        $ordersCreated = 0;

        foreach ($activeCarts as $cart) {
            // Determinar o novo status (70% completed, 30% abandoned)
            $shouldComplete = rand(1, 100) <= 70;
            
            if ($shouldComplete) {
                $this->processCompletedCart($cart);
                $completed++;
                $ordersCreated++;
            } else {
                $this->processAbandonedCart($cart);
                $abandoned++;
            }

            // Pequena pausa para simular processamento real
            usleep(100000); // 0.1 segundo
        }

        // Processar orders pendentes (aprovar algumas)
       // $this->processOrders();

        // Mostrar resumo
        $this->showProcessingSummary($activeCarts->count(), $completed, $abandoned, $ordersCreated);

        $this->command->info('✅ Processamento de carrinhos e orders concluído!');
    }

    /**
     * Processar carrinho como completado e criar order
     */
    private function processCompletedCart(Cart $cart): void
    {
        try {
            // Atualizar status do carrinho
            $cart->update(['status' => 'completed']);

            // Criar order correspondente
            $order = Order::create([
                'uuid' => Str::uuid(),
                'user_id' => $cart->user_id,
                'plan_id' => $cart->plan_id,
                'user_metadata' => $this->getUserMetadata($cart->user),
                'plan_metadata' => $this->getPlanMetadata($cart->plan),
                'status' => 'pending' // Orders iniciam como pending
            ]);

            $cart->update(['order_id' => $order->id]);

            $this->command->line("  ✅ Carrinho {$cart->uuid} → Completado | Order: {$order->uuid}");

        } catch (\Exception $e) {
            $this->command->error("  ❌ Erro ao processar carrinho {$cart->uuid}: {$e->getMessage()}");
        }
    }

    /**
     * Processar carrinho como abandonado
     */
    private function processAbandonedCart(Cart $cart): void
    {
        try {
            // Atualizar status do carrinho
            $cart->update(['status' => 'abandoned']);

            $this->command->line("  🚫 Carrinho {$cart->uuid} → Abandonado");

        } catch (\Exception $e) {
            $this->command->error("  ❌ Erro ao abandonar carrinho {$cart->uuid}: {$e->getMessage()}");
        }
    }

    /**
     * Obter metadata do usuário
     */
    private function getUserMetadata(User $user): array
    {
        return [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'phone' => $user->phone,
            'role' => $user->role,
            'status' => $user->status,
            'sponsor_id' => $user->sponsor_id,
            'created_at' => $user->created_at?->toISOString(),
            'snapshot_date' => now()->toISOString()
        ];
    }

    /**
     * Obter metadata do plano
     */
    private function getPlanMetadata(Plan $plan): array
    {
        return [
            'id' => $plan->id,
            'uuid' => $plan->uuid,
            'name' => $plan->name,
            'description' => $plan->description,
            'price' => $plan->price,
            'status' => $plan->status,
            'commission_level_1' => $plan->commission_level_1,
            'commission_level_2' => $plan->commission_level_2,
            'commission_level_3' => $plan->commission_level_3,
            'is_promotional' => $plan->is_promotional,
            'start_date' => $plan->start_date?->toISOString(),
            'end_date' => $plan->end_date?->toISOString(),
            'created_at' => $plan->created_at?->toISOString(),
            'snapshot_date' => now()->toISOString()
        ];
    }


    /**
     * Mostrar resumo do processamento de orders
     */
    private function showOrdersProcessingSummary(int $total, int $approved, int $rejected, int $cancelled): void
    {
        $this->command->info('');
        $this->command->info('📋 RESUMO DO PROCESSAMENTO DE ORDERS');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info("📦 Total de orders processadas: {$total}");
        $this->command->info("✅ Orders aprovadas: {$approved}");
        $this->command->info("❌ Orders rejeitadas: {$rejected}");
        $this->command->info("🚫 Orders canceladas: {$cancelled}");
        
        if ($total > 0) {
            $approvalRate = round(($approved / $total) * 100, 2);
            $rejectionRate = round(($rejected / $total) * 100, 2);
            $cancellationRate = round(($cancelled / $total) * 100, 2);
            $this->command->info("📈 Taxa de aprovação: {$approvalRate}%");
            $this->command->info("📉 Taxa de rejeição: {$rejectionRate}%");
            $this->command->info("🚫 Taxa de cancelamento: {$cancellationRate}%");
        }
        
        $this->command->info('═══════════════════════════════════════');
    }

    /**
     * Mostrar resumo do processamento
     */
    private function showProcessingSummary(int $total, int $completed, int $abandoned, int $ordersCreated): void
    {
        $this->command->info('');
        $this->command->info('📊 RESUMO DO PROCESSAMENTO DE CARRINHOS');
        $this->command->info('══════════════════════════════════════');
        $this->command->info("🛍️ Total de carrinhos processados: {$total}");
        $this->command->info("✅ Carrinhos completados: {$completed}");
        $this->command->info("🚫 Carrinhos abandonados: {$abandoned}");
        $this->command->info("📦 Orders criadas: {$ordersCreated}");
        
        if ($total > 0) {
            $completionRate = round(($completed / $total) * 100, 2);
            $abandonmentRate = round(($abandoned / $total) * 100, 2);
            $this->command->info("📈 Taxa de conversão: {$completionRate}%");
            $this->command->info("📉 Taxa de abandono: {$abandonmentRate}%");
        }
        
        $this->command->info('══════════════════════════════════════');
    }

    /**
     * Mostrar estatísticas detalhadas dos carrinhos
     */
    private function showCartStatistics(): void
    {
        $this->command->info('📊 Estatísticas dos Carrinhos:');
        
        $activeCount = Cart::where('status', 'active')->count();
        $completedCount = Cart::where('status', 'completed')->count();
        $abandonedCount = Cart::where('status', 'abandoned')->count();
        $totalCarts = Cart::count();
        
        $this->command->line("  🔄 Carrinhos Ativos: {$activeCount}");
        $this->command->line("  ✅ Carrinhos Completados: {$completedCount}");
        $this->command->line("  🚫 Carrinhos Abandonados: {$abandonedCount}");
        $this->command->line("  📊 Total de Carrinhos: {$totalCarts}");
        
        if ($totalCarts > 0) {
            $activePercent = round(($activeCount / $totalCarts) * 100, 1);
            $completedPercent = round(($completedCount / $totalCarts) * 100, 1);
            $abandonedPercent = round(($abandonedCount / $totalCarts) * 100, 1);
            
            $this->command->line("  📈 Distribuição: {$activePercent}% ativos | {$completedPercent}% completados | {$abandonedPercent}% abandonados");
        }
    }

    /**
     * Validar integridade dos dados antes do processamento
     */
    private function validateDataIntegrity(): bool
    {
        $this->command->info('🔍 Validando integridade dos dados...');
        
        // Verificar se há carrinhos órfãos (sem usuário ou plano)
        $orphanCarts = Cart::leftJoin('users', 'carts.user_id', '=', 'users.id')
            ->leftJoin('plans', 'carts.plan_id', '=', 'plans.id')
            ->whereNull('users.id')
            ->orWhereNull('plans.id')
            ->count();
            
        if ($orphanCarts > 0) {
            $this->command->warn("⚠️ Encontrados {$orphanCarts} carrinhos órfãos (sem usuário ou plano válido)");
            return false;
        }
        
        $this->command->info('✅ Integridade dos dados validada');
        return true;
    }
}