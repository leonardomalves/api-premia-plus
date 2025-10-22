<?php

namespace Database\Seeders;

use App\Models\FinancialStatement;
use App\Models\Raffle;
use App\Models\RaffleTicket;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserApplyToRaffleSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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

        foreach ($users as $user) {
            $wallet = $user->wallet;
            $initialBalance = $wallet->balance;
            
            $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->command->info("👤 {$user->name} (ID: {$user->id})");
            $this->command->info("💰 Saldo inicial: R$ " . number_format($initialBalance, 2, ',', '.'));

            // Cada usuário aplica em 3-8 rifas aleatórias
            $rafflesToApply = $activeRaffles->random(rand(3, min(8, $activeRaffles->count())));
            $userApplications = 0;
            $userTickets = 0;
            $userSpent = 0;

            foreach ($rafflesToApply as $raffle) {
                // Calcular custo mínimo para participar (min_tickets_required)
                $minCost = $raffle->min_tickets_required * $raffle->unit_ticket_value;
                
                // Verificar se tem saldo suficiente para a quantidade mínima
                if ($wallet->balance < $minCost) {
                    $this->command->warn("  ⚠️  Saldo insuficiente: R$ " . number_format($wallet->balance, 2, ',', '.') . " < R$ " . number_format($minCost, 2, ',', '.') . " ({$raffle->min_tickets_required} tickets) - {$raffle->title}");
                    continue;
                }

                // Determinar quantos tickets comprar (entre min_tickets_required e o que o saldo permite)
                $maxAffordable = floor($wallet->balance / $raffle->unit_ticket_value);
                
                // Comprar entre o mínimo e até 3x o mínimo (ou o que puder pagar)
                $ticketsToApply = min(
                    rand($raffle->min_tickets_required, $raffle->min_tickets_required * 3),
                    $maxAffordable
                );

                // Garantir que está comprando pelo menos o mínimo exigido
                if ($ticketsToApply < $raffle->min_tickets_required) {
                    $ticketsToApply = $raffle->min_tickets_required;
                }

                $totalCost = $ticketsToApply * $raffle->unit_ticket_value;

                DB::beginTransaction();

                try {
                    // Buscar tickets disponíveis do pool
                    $availableTickets = Ticket::whereDoesntHave('raffleTickets', function ($query) use ($raffle) {
                        $query->where('raffle_id', $raffle->id);
                    })
                        ->inRandomOrder()
                        ->limit($ticketsToApply)
                        ->get();

                    if ($availableTickets->count() < $ticketsToApply) {
                        throw new \Exception("Tickets insuficientes no pool");
                    }

                    // Debitar saldo da wallet
                    $wallet->balance -= $totalCost;
                    $wallet->save();

                    // Criar entrada de débito no FinancialStatement
                    FinancialStatement::create([
                        'uuid' => Str::uuid(),
                        'user_id' => $user->id,
                        'correlation_id' => $raffle->uuid,
                        'amount' => $totalCost,
                        'type' => 'debit',
                        'description' => $this->generateDebitDescription($raffle, $ticketsToApply, $totalCost),
                        'status' => 'completed',
                        'origin' => 'raffle',
                    ]);

                    // Criar registros em raffle_tickets
                    $ticketNumbers = [];
                    foreach ($availableTickets as $ticket) {
                        RaffleTicket::create([
                            'uuid' => Str::uuid(),
                            'user_id' => $user->id,
                            'raffle_id' => $raffle->id,
                            'ticket_id' => $ticket->id,
                            'status' => 'confirmed',
                        ]);
                        $ticketNumbers[] = $ticket->number;
                    }

                    DB::commit();

                    $userApplications++;
                    $userTickets += $ticketsToApply;
                    $userSpent += $totalCost;

                    $this->command->info("  ✅ {$raffle->title}");
                    $this->command->info("     Tickets: {$ticketsToApply} x R$ " . number_format($raffle->unit_ticket_value, 2, ',', '.') . " = R$ " . number_format($totalCost, 2, ',', '.'));
                    $this->command->info("     Números: " . implode(', ', array_slice($ticketNumbers, 0, 10)) . (count($ticketNumbers) > 10 ? '...' : ''));

                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->command->error("  ❌ Erro: {$e->getMessage()}");
                    continue;
                }
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
        $this->command->info("📈 Média por usuário: " . round($totalApplications / $users->count(), 1) . " rifas");
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('✅ Processo concluído com sucesso!');
    }

    /**
     * Gera descrição para o débito no extrato financeiro
     */
    protected function generateDebitDescription(Raffle $raffle, int $tickets, float $amount): string
    {
        $unitValue = $amount / $tickets;
        
        return sprintf(
            'Débito referente à aplicação em rifa - %s (%d ticket%s x R$ %.2f)',
            $raffle->title,
            $tickets,
            $tickets > 1 ? 's' : '',
            $unitValue
        );
    }
}
