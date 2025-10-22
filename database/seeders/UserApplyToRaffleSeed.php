<?php

namespace Database\Seeders;

use App\Jobs\UserApplyToRaffleJob;
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
        $this->command->info('🎫 Iniciando aplicação de usuários em rifas via Jobs...');

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

        $totalJobsDispatched = 0;

        foreach ($users as $user) {
            $wallet = $user->wallet;
            
            $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->command->info("👤 {$user->name} (ID: {$user->id})");
            $this->command->info("💰 Saldo atual: R$ " . number_format($wallet->balance, 2, ',', '.'));

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

            // Calcular quantas rifas o usuário pode aplicar com base no saldo
            $maxRafflesAffordable = 0;
            $affordableRaffles = [];
            
            foreach ($availableRaffles as $raffle) {
                $cost = $raffle->min_tickets_required * $raffle->unit_ticket_value;
                if ($wallet->balance >= $cost) {
                    $affordableRaffles[] = $raffle;
                    $maxRafflesAffordable++;
                }
            }

            if (empty($affordableRaffles)) {
                $this->command->warn("  ⚠️  Saldo insuficiente para aplicar em qualquer rifa");
                continue;
            }

            // Aplicar em 3-8 rifas aleatórias (limitado pelo que pode pagar)
            $numberOfRaffles = rand(3, min(8, count($affordableRaffles)));
            $rafflesToApply = collect($affordableRaffles)
                ->random(min($numberOfRaffles, count($affordableRaffles)));

            $userJobsDispatched = 0;

            foreach ($rafflesToApply as $raffle) {
                // Dispatch do job para fila
                UserApplyToRaffleJob::dispatch(
                    $user,
                    $raffle,
                    $raffle->min_tickets_required
                );

                $userJobsDispatched++;
                $this->command->info("  📤 Job enviado: {$raffle->title}");
            }

            $this->command->info("📊 Jobs enviados para o usuário: {$userJobsDispatched}");
            $totalJobsDispatched += $userJobsDispatched;
        }

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('📊 ESTATÍSTICAS FINAIS');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info("👥 Usuários processados: {$users->count()}");
        $this->command->info("📤 Total de Jobs despachados: {$totalJobsDispatched}");
        $this->command->info("📈 Média por usuário: " . round($totalJobsDispatched / max($users->count(), 1), 1) . " jobs");
        $this->command->info('');
        $this->command->info('⚙️  Para processar os jobs, execute:');
        $this->command->info('   php artisan queue:work');
        $this->command->info('');
        $this->command->info('� Para monitorar a fila:');
        $this->command->info('   php artisan queue:listen');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('✅ Jobs enfileirados com sucesso!');
    }
}