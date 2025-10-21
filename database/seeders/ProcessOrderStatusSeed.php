<?php

namespace Database\Seeders;

use App\Jobs\ExecuteBusinessRuleJob;
use App\Models\Order;
use Illuminate\Database\Seeder;

class ProcessOrderStatusSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('📦 Iniciando processamento de status das orders...');

        // Buscar todas as orders pendentes
        $pendingOrders = Order::where('status', 'pending')->get();

        if ($pendingOrders->isEmpty()) {
            $this->command->warn('⚠️ Nenhuma order pendente encontrada');

            return;
        }

        $this->command->info("📊 Encontradas {$pendingOrders->count()} orders pendentes para processar");

        $approved = 0;
        $rejected = 0;
        $cancelled = 0;

        foreach ($pendingOrders as $order) {
            // Distribuir status: 70% aprovado, 20% rejeitado, 10% cancelado
            $random = rand(1, 100);

            if ($random <= 70) {
                $this->processApprovedOrder($order);
                $approved++;
            } elseif ($random <= 90) {
                $this->processRejectedOrder($order);
                $rejected++;
            } else {
                $this->processCancelledOrder($order);
                $cancelled++;
            }

            // Pequena pausa para simular processamento real
            usleep(50000); // 0.05 segundo
        }

        // Mostrar resumo
        $this->showOrderProcessingSummary($pendingOrders->count(), $approved, $rejected, $cancelled);

        $this->command->info('✅ Processamento de orders concluído!');
    }

    /**
     * Processar order como aprovada
     */
    private function processApprovedOrder(Order $order): void
    {
        try {
            $order->update(['status' => 'approved']);

            $userEmail = $order->user_metadata['email'] ?? 'N/A';
            $planName = $order->plan_metadata['name'] ?? 'N/A';

            $this->command->line("  ✅ Order {$order->uuid} → Aprovada | {$userEmail} | {$planName}");
            ExecuteBusinessRuleJob::dispatch($order->id);

        } catch (\Exception $e) {
            $this->command->error("  ❌ Erro ao aprovar order {$order->uuid}: {$e->getMessage()}");
        }
    }

    /**
     * Processar order como rejeitada
     */
    private function processRejectedOrder(Order $order): void
    {
        try {
            $order->update(['status' => 'rejected']);

            $userEmail = $order->user_metadata['email'] ?? 'N/A';
            $planName = $order->plan_metadata['name'] ?? 'N/A';

            $this->command->line("  ❌ Order {$order->uuid} → Rejeitada | {$userEmail} | {$planName}");

        } catch (\Exception $e) {
            $this->command->error("  ❌ Erro ao rejeitar order {$order->uuid}: {$e->getMessage()}");
        }
    }

    /**
     * Processar order como cancelada
     */
    private function processCancelledOrder(Order $order): void
    {
        try {
            $order->update(['status' => 'cancelled']);

            $userEmail = $order->user_metadata['email'] ?? 'N/A';
            $planName = $order->plan_metadata['name'] ?? 'N/A';

            $this->command->line("  🚫 Order {$order->uuid} → Cancelada | {$userEmail} | {$planName}");

        } catch (\Exception $e) {
            $this->command->error("  ❌ Erro ao cancelar order {$order->uuid}: {$e->getMessage()}");
        }
    }

    /**
     * Mostrar resumo do processamento de orders
     */
    private function showOrderProcessingSummary(int $total, int $approved, int $rejected, int $cancelled): void
    {
        $this->command->info('');
        $this->command->info('📊 RESUMO DO PROCESSAMENTO DE ORDERS');
        $this->command->info('═══════════════════════════════════');
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

        $this->command->info('═══════════════════════════════════');

        // Mostrar estatísticas gerais
        $this->showOrderStatistics();
    }

    /**
     * Mostrar estatísticas gerais das orders
     */
    private function showOrderStatistics(): void
    {
        $this->command->info('');
        $this->command->info('📊 Estatísticas Gerais das Orders:');

        $pendingCount = Order::where('status', 'pending')->count();
        $approvedCount = Order::where('status', 'approved')->count();
        $rejectedCount = Order::where('status', 'rejected')->count();
        $cancelledCount = Order::where('status', 'cancelled')->count();
        $totalOrders = Order::count();

        $this->command->line("  ⏳ Orders Pendentes: {$pendingCount}");
        $this->command->line("  ✅ Orders Aprovadas: {$approvedCount}");
        $this->command->line("  ❌ Orders Rejeitadas: {$rejectedCount}");
        $this->command->line("  🚫 Orders Canceladas: {$cancelledCount}");
        $this->command->line("  📊 Total de Orders: {$totalOrders}");

        if ($totalOrders > 0) {
            $pendingPercent = round(($pendingCount / $totalOrders) * 100, 1);
            $approvedPercent = round(($approvedCount / $totalOrders) * 100, 1);
            $rejectedPercent = round(($rejectedCount / $totalOrders) * 100, 1);
            $cancelledPercent = round(($cancelledCount / $totalOrders) * 100, 1);

            $this->command->line("  📈 Distribuição: {$pendingPercent}% pendentes | {$approvedPercent}% aprovadas | {$rejectedPercent}% rejeitadas | {$cancelledPercent}% canceladas");
        }
    }

    /**
     * Mostrar orders por plano
     */
    private function showOrdersByPlan(): void
    {
        $this->command->info('');
        $this->command->info('📊 Orders por Plano:');

        $orders = Order::all();
        $planStats = [];

        foreach ($orders as $order) {
            $planName = $order->plan_metadata['name'] ?? 'Plano Desconhecido';
            $status = $order->status;

            if (! isset($planStats[$planName])) {
                $planStats[$planName] = [
                    'total' => 0,
                    'approved' => 0,
                    'rejected' => 0,
                    'cancelled' => 0,
                    'pending' => 0,
                ];
            }

            $planStats[$planName]['total']++;
            $planStats[$planName][$status]++;
        }

        foreach ($planStats as $planName => $stats) {
            $approvalRate = $stats['total'] > 0 ? round(($stats['approved'] / $stats['total']) * 100, 1) : 0;
            $this->command->line("  📦 {$planName}: {$stats['total']} orders ({$approvalRate}% aprovação)");
        }
    }
}
