<?php

namespace Database\Seeders;

use App\Models\Raffle;
use App\Models\User;
use App\Services\BusinessRules\UserApplyToRaffleService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserApplyToRaffleSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $service = new UserApplyToRaffleService();
        
        $this->command->info('🎫 Iniciando aplicação de usuários em rifas...');

        // Buscar usuários com saldo em wallet (exceto admins)
        $users = User::where('role', '!=', 'admin')
            ->whereHas('wallet', function ($query) {
                $query->where('balance', '>', 0);
            })
            ->with('wallet')
            ->limit(10) // Processar 10 usuários para demo
            ->get();

        if ($users->isEmpty()) {
            $this->command->error('❌ Nenhum usuário com saldo encontrado!');
            return;
        }

        // Buscar rifas ativas
        $activeRaffles = Raffle::where('status', 'active')
            ->inRandomOrder()
            ->limit(50) // 50 rifas aleatórias
            ->get();

        if ($activeRaffles->isEmpty()) {
            $this->command->error('❌ Nenhuma rifa ativa encontrada!');
            return;
        }

        $this->command->info("👥 Usuários com saldo: {$users->count()}");
        $this->command->info("🎰 Rifas ativas disponíveis: {$activeRaffles->count()}");
        $this->command->info('');

        $totalApplications = 0;
        $totalTicketsPurchased = 0;
        $totalMoneySpent = 0;
        $totalErrors = 0;

        foreach ($users as $user) {
            $wallet = $user->wallet;
            $initialBalance = $wallet->balance;
            
            $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->command->info("👤 {$user->name} (ID: {$user->id})");
            $this->command->info("💰 Saldo inicial: R$ " . number_format($initialBalance, 2, ',', '.'));

            // Buscar rifas em que o usuário ainda NÃO aplicou
            $appliedRaffleIds = DB::table('raffle_tickets')
                ->where('user_id', $user->id)
                ->pluck('raffle_id')
                ->toArray();

            $availableRaffles = $activeRaffles->whereNotIn('id', $appliedRaffleIds);

            if ($availableRaffles->isEmpty()) {
                $this->command->warn("  ⚠️  Usuário já aplicou em todas as rifas disponíveis");
                continue;
            }

            // Cada usuário aplica em 3-8 rifas aleatórias (que ainda não aplicou)
            $rafflesToApply = $availableRaffles->random(rand(3, min(8, $availableRaffles->count())));
            $userApplications = 0;
            $userTickets = 0;
            $userSpent = 0;

            foreach ($rafflesToApply as $raffle) {
                // 🚀 USAR O SERVICE AQUI
                $result = $service->applyUserToRaffle(
                    $user,
                    $raffle,
                    $raffle->min_tickets_required
                );

                if ($result['success']) {
                    $userApplications++;
                    $userTickets += $result['tickets_count'];
                    $userSpent += $result['total_cost'];

                    $this->command->info("  ✅ {$result['raffle_title']}");
                    $this->command->info("     Tickets: {$result['tickets_count']} x R$ " . number_format($raffle->unit_ticket_value, 2, ',', '.') . " = R$ " . number_format($result['total_cost'], 2, ',', '.'));
                    $this->command->info("     Números: " . implode(', ', array_slice($result['ticket_numbers'], 0, 10)) . (count($result['ticket_numbers']) > 10 ? '...' : ''));
                    $this->command->info("     ⏱️  Tempo: {$result['duration_ms']}ms");
                } else {
                    $totalErrors++;
                    $this->command->warn("  ⚠️  {$raffle->title}: {$result['message']}");
                }

                // Refresh wallet para próxima iteração
                $wallet->refresh();
            }

            $this->command->info("📊 Resumo do usuário:");
            $this->command->info("   Rifas aplicadas: {$userApplications}");
            $this->command->info("   Total de tickets: {$userTickets}");
            $this->command->info("   Gasto total: R$ " . number_format($userSpent, 2, ',', '.'));
            $this->command->info("   Saldo restante: R$ " . number_format($wallet->balance, 2, ',', '.'));
            
            $totalApplications += $userApplications;
            $totalTicketsPurchased += $userTickets;
            $totalMoneySpent += $userSpent;
        }

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('📊 ESTATÍSTICAS FINAIS');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info("👥 Usuários processados: {$users->count()}");
        $this->command->info("🎫 Total de aplicações: {$totalApplications}");
        $this->command->info("🎰 Total de tickets comprados: {$totalTicketsPurchased}");
        $this->command->info("💸 Valor total gasto: R$ " . number_format($totalMoneySpent, 2, ',', '.'));
        $this->command->info("⚠️  Erros encontrados: {$totalErrors}");
        $this->command->info("📈 Média por usuário: " . round($totalApplications / max($users->count(), 1), 1) . " rifas");
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('✅ Processo concluído com sucesso!');
    }
}