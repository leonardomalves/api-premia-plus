<?php

namespace Database\Seeders;

use App\Models\Commission;
use App\Models\Order;
use App\Models\User;
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
        $planMetadata = $order->plan_metadata;

        $this->command->info("🛒 Processando order do usuário: {$buyer->name} (Plano: {$planMetadata['name']})");

        // Buscar uplines até o nível configurado
        $uplines = $this->getUplines($buyer, $this->maxLevels);

        if (empty($uplines)) {
            $this->command->warn("   ⚠️ Nenhum upline encontrado para {$buyer->name}");

            return;
        }

        // Calcular comissões para cada nível
        foreach ($uplines as $level => $upline) {
            $this->calculateLevelCommission($order, $upline, $level + 1, $planMetadata);
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

            if (! $sponsor) {
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
    private function calculateLevelCommission(Order $order, User $upline, int $level, array $planMetadata): void
    {
        $commissionRate = $this->getCommissionRateFromMetadata($planMetadata, $level);

        if ($commissionRate <= 0) {
            $this->command->warn("   ⚠️ Taxa de comissão zero para nível {$level}");

            return;
        }

        $planPrice = (float) $planMetadata['price'];
        $commissionAmount = $planPrice * ($commissionRate / 100);

        $this->command->info("   💰 Nível {$level}: {$upline->name} - R$ ".number_format($commissionAmount, 2, ',', '.')." ({$commissionRate}%)");

        // Aqui você pode salvar a comissão no banco de dados
        // Exemplo: Commission::create([...])
        $this->saveCommission($order, $upline, $level, $commissionAmount, $commissionRate);
    }

    /**
     * Obtém a taxa de comissão baseada nos metadados do plano e nível
     */
    private function getCommissionRateFromMetadata(array $planMetadata, int $level): float
    {
        return match ($level) {
            1 => (float) $planMetadata['commission_level_1'],
            2 => (float) $planMetadata['commission_level_2'],
            3 => (float) $planMetadata['commission_level_3'],
            default => 0.0
        };
    }

    /**
     * Salva a comissão calculada usando updateOrCreate para evitar duplicação
     */
    private function saveCommission(Order $order, User $upline, int $level, float $amount, float $rate): void
    {
        // Usar updateOrCreate para evitar duplicação
        $commission = Commission::updateOrCreate(
            [
                'order_id' => $order->id,
                'user_id' => $upline->id,
                'origin_user_id' => $order->user_id,
            ],
            [
                'amount' => $amount,
                'available_at' => now()->addDays(30), // Disponível em 30 dias
            ]
        );

        $this->command->info("   📝 Comissão salva: {$upline->name} - Nível {$level} - R$ ".number_format($amount, 2, ',', '.')." ({$rate}%)");

        if ($commission->wasRecentlyCreated) {
            $this->command->info("   ✅ Nova comissão criada (ID: {$commission->id})");
        } else {
            $this->command->info("   🔄 Comissão atualizada (ID: {$commission->id})");
        }
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
        $this->command->info('   - Orders processadas: '.Order::where('status', 'approved')->count());
        $this->command->info('   - Usuários com uplines: '.User::whereNotNull('sponsor_id')->count());

        // Estatísticas das comissões
        $totalCommissions = Commission::count();
        $totalAmount = Commission::sum('amount');
        $paidCommissions = Commission::where('paid', true)->count();
        $availableCommissions = Commission::where('available_at', '<=', now())->where('paid', false)->count();

        $this->command->info('💰 Estatísticas de Comissões Salvas:');
        $this->command->info("   - Total de comissões: {$totalCommissions}");
        $this->command->info('   - Valor total: R$ '.number_format($totalAmount, 2, ',', '.'));
        $this->command->info("   - Comissões pagas: {$paidCommissions}");
        $this->command->info("   - Comissões disponíveis: {$availableCommissions}");

        // Mostrar exemplo de metadados de plano
        $order = Order::where('status', 'approved')->first();
        if ($order && $order->plan_metadata) {
            $this->command->info('📦 Exemplo de metadados de plano:');
            $this->command->info("   - Nome: {$order->plan_metadata['name']}");
            $this->command->info('   - Preço: R$ '.number_format($order->plan_metadata['price'], 2, ',', '.'));
            $this->command->info("   - Comissão Nível 1: {$order->plan_metadata['commission_level_1']}%");
            $this->command->info("   - Comissão Nível 2: {$order->plan_metadata['commission_level_2']}%");
            $this->command->info("   - Comissão Nível 3: {$order->plan_metadata['commission_level_3']}%");
        }
    }
}
