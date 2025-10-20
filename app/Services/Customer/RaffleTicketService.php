<?php

namespace App\Services\Customer;

use App\Models\Raffle;
use App\Models\RaffleTicket;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RaffleTicketService
{
    /**
     * Aplica tickets de um usuário em uma rifa
     * 
     * @param User $user Usuário que está aplicando
     * @param Raffle $raffle Rifa onde os tickets serão aplicados
     * @param int $quantity Quantidade de tickets a aplicar (opcional, padrão é o mínimo da rifa)
     * @return array Resultado da operação
     * @throws \Exception
     */
    public function applyTicketsToRaffle(User $user, Raffle $raffle, ?int $quantity = null): array
    {
        // Definir quantidade (usar mínimo da rifa se não especificado)
        $ticketsToApply = $quantity ?? $raffle->tickets_required;
        
        // Validar quantidade mínima
        if ($ticketsToApply < $raffle->tickets_required) {
            throw new \Exception("Quantidade mínima de tickets para esta rifa: {$raffle->tickets_required}");
        }
        
        // Validar quantidade máxima por usuário
        if ($raffle->max_tickets_per_user > 0) {
            $currentTickets = RaffleTicket::where('raffle_id', $raffle->id)
                ->where('user_id', $user->id)
                ->whereIn('status', [RaffleTicket::STATUS_PENDING, RaffleTicket::STATUS_CONFIRMED])
                ->count();
            
            if (($currentTickets + $ticketsToApply) > $raffle->max_tickets_per_user) {
                $remaining = $raffle->max_tickets_per_user - $currentTickets;
                throw new \Exception("Você só pode aplicar mais {$remaining} tickets nesta rifa (limite: {$raffle->max_tickets_per_user})");
            }
        }
        
        // Buscar tickets do usuário que atendem o nível mínimo
        $walletTickets = $user->walletTickets()
            ->where('ticket_level', '>=', $raffle->min_ticket_level)
            ->where('status', 'active')
            ->orderBy('ticket_level', 'asc')
            ->get();
        
        // Calcular tickets disponíveis
        $availableTickets = $walletTickets->sum(function($ticket) {
            return $ticket->available_tickets;
        });
        
        if ($availableTickets < $ticketsToApply) {
            throw new \Exception("Tickets insuficientes. Você possui: {$availableTickets}, Necessário: {$ticketsToApply}");
        }
        
        return DB::transaction(function () use ($user, $raffle, $ticketsToApply, $walletTickets) {
            $remainingToApply = $ticketsToApply;
            $appliedTickets = [];
            $walletUpdates = [];
            
            foreach ($walletTickets as $walletTicket) {
                if ($remainingToApply <= 0) {
                    break;
                }
                
                $available = $walletTicket->available_tickets;
                
                if ($available > 0) {
                    $toDecrement = min($remainingToApply, $available);
                    
                    // Buscar tickets disponíveis do pool
                    $poolTickets = Ticket::whereDoesntHave('raffleTickets', function($query) use ($raffle) {
                        $query->where('raffle_id', $raffle->id);
                    })
                    ->inRandomOrder()
                    ->limit($toDecrement)
                    ->get();
                    
                    if ($poolTickets->count() < $toDecrement) {
                        throw new \Exception("Tickets insuficientes no pool global");
                    }
                    
                    // Decrementar do wallet
                    $decremented = $walletTicket->decrementIn($toDecrement);
                    
                    if ($decremented !== $toDecrement) {
                        throw new \Exception("Erro ao decrementar tickets do wallet");
                    }
                    
                    // Criar registros em raffle_tickets
                    foreach ($poolTickets as $ticket) {
                        $raffleTicket = RaffleTicket::create([
                            'user_id' => $user->id,
                            'raffle_id' => $raffle->id,
                            'ticket_id' => $ticket->id,
                            'status' => RaffleTicket::STATUS_CONFIRMED,
                        ]);
                        
                        $appliedTickets[] = [
                            'ticket_number' => $ticket->number,
                            'ticket_id' => $ticket->id,
                            'raffle_ticket_id' => $raffleTicket->id,
                        ];
                    }
                    
                    $walletUpdates[] = [
                        'wallet_id' => $walletTicket->id,
                        'ticket_level' => $walletTicket->ticket_level,
                        'decremented' => $decremented,
                    ];
                    
                    $remainingToApply -= $decremented;
                }
            }
            
            if ($remainingToApply > 0) {
                throw new \Exception("Não foi possível aplicar todos os tickets solicitados");
            }
            
            return [
                'success' => true,
                'message' => 'Tickets aplicados com sucesso na rifa',
                'raffle' => [
                    'id' => $raffle->id,
                    'uuid' => $raffle->uuid,
                    'title' => $raffle->title,
                ],
                'applied_tickets' => $appliedTickets,
                'total_applied' => count($appliedTickets),
                'wallet_updates' => $walletUpdates,
            ];
        });
    }
    
    /**
     * Cancela tickets aplicados em uma rifa
     * 
     * @param User $user Usuário
     * @param Raffle $raffle Rifa
     * @param array $ticketIds IDs dos tickets a cancelar (opcional, cancela todos se não especificado)
     * @return array
     */
    public function cancelTicketsFromRaffle(User $user, Raffle $raffle, ?array $ticketIds = null): array
    {
        return DB::transaction(function () use ($user, $raffle, $ticketIds) {
            $query = RaffleTicket::where('user_id', $user->id)
                ->where('raffle_id', $raffle->id)
                ->whereIn('status', [RaffleTicket::STATUS_PENDING, RaffleTicket::STATUS_CONFIRMED]);
            
            if ($ticketIds) {
                $query->whereIn('id', $ticketIds);
            }
            
            $raffleTickets = $query->get();
            
            if ($raffleTickets->isEmpty()) {
                throw new \Exception('Nenhum ticket encontrado para cancelar');
            }
            
            $cancelled = [];
            
            foreach ($raffleTickets as $raffleTicket) {
                // Marcar como cancelado
                $raffleTicket->markAsCancelled();
                
                // Devolver ticket ao wallet (implementar lógica de devolução)
                // TODO: Implementar devolução ao wallet
                
                $cancelled[] = [
                    'raffle_ticket_id' => $raffleTicket->id,
                    'ticket_id' => $raffleTicket->ticket_id,
                    'ticket_number' => $raffleTicket->ticket->number,
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Tickets cancelados com sucesso',
                'cancelled_tickets' => $cancelled,
                'total_cancelled' => count($cancelled),
            ];
        });
    }
    
    /**
     * Lista tickets de um usuário em uma rifa
     * 
     * @param User $user
     * @param Raffle $raffle
     * @return array
     */
    public function getUserTicketsInRaffle(User $user, Raffle $raffle): array
    {
        $raffleTickets = RaffleTicket::with('ticket')
            ->where('user_id', $user->id)
            ->where('raffle_id', $raffle->id)
            ->get();
        
        return [
            'raffle' => [
                'id' => $raffle->id,
                'uuid' => $raffle->uuid,
                'title' => $raffle->title,
            ],
            'tickets' => $raffleTickets->map(function($rt) {
                return [
                    'raffle_ticket_id' => $rt->id,
                    'ticket_id' => $rt->ticket_id,
                    'ticket_number' => $rt->ticket->number,
                    'status' => $rt->status,
                    'created_at' => $rt->created_at,
                ];
            }),
            'total_tickets' => $raffleTickets->count(),
            'by_status' => [
                'pending' => $raffleTickets->where('status', RaffleTicket::STATUS_PENDING)->count(),
                'confirmed' => $raffleTickets->where('status', RaffleTicket::STATUS_CONFIRMED)->count(),
                'winner' => $raffleTickets->where('status', RaffleTicket::STATUS_WINNER)->count(),
                'loser' => $raffleTickets->where('status', RaffleTicket::STATUS_LOSER)->count(),
            ],
        ];
    }
}
