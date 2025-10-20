<?php

namespace Database\Seeders;

use App\Models\Raffle;
use App\Models\RaffleTicket;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserApplyToRaffleSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::find(9);
        
        if (!$user) {
            $this->command->error('Usuário com ID 9 não encontrado!');
            return;
        }
        
        $raffles = Raffle::all();
        
        if ($raffles->isEmpty()) {
            $this->command->error('Nenhuma rifa encontrada!');
            return;
        }
        
        foreach ($raffles as $raffle) {
            $minTicketLevel = $raffle->min_ticket_level;
            $ticketsRequired = $raffle->tickets_required;
            
            // Buscar tickets do usuário que atendem o nível mínimo da rifa
            $userTickets = $user->walletTickets()
                ->with('plan') // Carregar o plano para obter o preço
                ->where('ticket_level', '>=', $minTicketLevel)
                ->where('status', 'active')
                ->orderBy('ticket_level', 'asc') // Usar tickets de menor nível primeiro
                ->get();

            // Calcular tickets disponíveis
            $availableTickets = $userTickets->sum(function($ticket) {
                return ($ticket->total_tickets - $ticket->total_tickets_used) + $ticket->bonus_tickets;
            });

            echo "\n========================================\n";
            echo "Rifa: {$raffle->title}\n";
            echo "Nível mínimo: {$minTicketLevel}\n";
            echo "Tickets requeridos: {$ticketsRequired}\n";
            echo "Tickets disponíveis do usuário: {$availableTickets}\n";
            
            // Verificar se o usuário tem tickets suficientes
            if ($availableTickets < $ticketsRequired) {
                echo "❌ INSUFICIENTE - Usuário não tem tickets suficientes!\n";
                continue;
            }
            
            // Aplicar os tickets na rifa com transação
            $remainingToApply = $ticketsRequired;
            $totalApplied = 0;
            $allTicketsApplied = [];
            
            DB::beginTransaction();
            
            try {
                foreach ($userTickets as $walletTicket) {
                    if ($remainingToApply <= 0) {
                        break;
                    }
                    
                    // Verificar quantos tickets disponíveis neste wallet
                    $available = ($walletTicket->total_tickets - $walletTicket->total_tickets_used) 
                               + $walletTicket->bonus_tickets;
                    
                    if ($available > 0) {
                        // Decrementar a quantidade necessária (ou o disponível, o que for menor)
                        $toDecrement = min($remainingToApply, $available);
                        
                        // Buscar tickets disponíveis do pool (não vinculados a esta rifa ainda)
                        $availableTickets = Ticket::whereDoesntHave('raffleTickets', function($query) use ($raffle) {
                            $query->where('raffle_id', $raffle->id);
                        })
                        ->inRandomOrder()
                        ->limit($toDecrement)
                        ->get();
                        
                        if ($availableTickets->count() < $toDecrement) {
                            throw new \Exception("Tickets insuficientes no pool. Necessário: {$toDecrement}, Disponível: {$availableTickets->count()}");
                        }
                        
                        // Decrementar do wallet
                        $decremented = $walletTicket->decrementIn($toDecrement);
                        
                        if ($decremented !== $toDecrement) {
                            throw new \Exception("Erro ao decrementar wallet. Esperado: {$toDecrement}, Decrementado: {$decremented}");
                        }
                        
                        // Criar registros em raffle_tickets
                        $ticketsCreated = [];
                        foreach ($availableTickets as $ticket) {
                            RaffleTicket::create([
                                'user_id' => $user->id,
                                'raffle_id' => $raffle->id,
                                'ticket_id' => $ticket->id,
                                'status' => RaffleTicket::STATUS_CONFIRMED,
                            ]);
                            
                            $ticketsCreated[] = $ticket->number;
                            $allTicketsApplied[] = $ticket->number;
                        }
                        
                        $totalApplied += count($ticketsCreated);
                        $remainingToApply -= count($ticketsCreated);
                        
                        echo "  -> Aplicado {$decremented} tickets do wallet #{$walletTicket->id} (Nível: {$walletTicket->ticket_level}) - Tickets: " . implode(', ', array_slice($ticketsCreated, 0, 10)) . (count($ticketsCreated) > 10 ? '...' : '') . "\n";
                    }
                }
                
                // Se não conseguiu aplicar todos os tickets necessários, fazer rollback
                if ($totalApplied < $ticketsRequired) {
                    throw new \Exception("Não foi possível aplicar todos os tickets necessários. Aplicado: {$totalApplied}, Necessário: {$ticketsRequired}");
                }
                
                DB::commit();
                
            } catch (\Exception $e) {
                DB::rollBack();
                echo "  ❌ ERRO: {$e->getMessage()}\n";
                continue;
            }
            
            if ($totalApplied === $ticketsRequired) {
                echo "✅ SUCESSO - {$totalApplied} tickets aplicados na rifa!\n";
                echo "   Números aplicados: " . implode(', ', array_slice($allTicketsApplied, 0, 20)) . (count($allTicketsApplied) > 20 ? '...' : '') . "\n";
            } else {
                echo "⚠️ PARCIAL - Apenas {$totalApplied} de {$ticketsRequired} tickets foram aplicados\n";
            }
        }
        
        echo "\n========================================\n";
        echo "Processo concluído!\n";
    }
}
