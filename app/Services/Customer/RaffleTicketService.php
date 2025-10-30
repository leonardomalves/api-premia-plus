<?php

declare(strict_types=1);

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
     * @param  User  $user  Usuário que está aplicando
     * @param  Raffle  $raffle  Rifa onde os tickets serão aplicados
     * @param  int  $quantity  Quantidade de tickets a aplicar (opcional, padrão é o mínimo da rifa)
     * @return array Resultado da operação
     *
     * @throws \Exception
     */
    public function applyTicketsToRaffle(User $user, Raffle $raffle, ?int $quantity = null): array
    {
        // Validar quantidade
        if ($quantity === null || $quantity < 1) {
            throw new \Exception('Quantidade deve ser maior que zero');
        }

        $ticketsToApply = $quantity;

        // Validar se a rifa está ativa
        if ($raffle->status !== 'active') {
            throw new \Exception('Esta rifa não está ativa.');
        }

        // Validar quantidade máxima por usuário
        if ($raffle->max_tickets_per_user > 0) {
            $currentTickets = RaffleTicket::where('raffle_id', $raffle->id)
                ->where('user_id', $user->id)
                ->whereIn('status', [RaffleTicket::STATUS_PENDING, RaffleTicket::STATUS_CONFIRMED])
                ->count();

            if (($currentTickets + $ticketsToApply) > $raffle->max_tickets_per_user) {
                $remaining = $raffle->max_tickets_per_user - $currentTickets;
                throw new \Exception("Quantidade excede o limite de {$raffle->max_tickets_per_user} tickets por usuário para esta rifa.");
            }
        }

        // Buscar tickets do usuário que atendem o nível mínimo
        $walletTickets = $user->walletTickets()
            ->where('ticket_level', '>=', $raffle->min_ticket_level)
            ->where('status', 'active')
            ->where('total_tickets', '>', 0)
            ->orderBy('ticket_level', 'asc')
            ->get();

        // Calcular tickets disponíveis
        $availableTickets = $walletTickets->sum('total_tickets');

        if ($availableTickets == 0) {
            throw new \Exception("Você não possui tickets do nível mínimo exigido ({$raffle->min_ticket_level}).");
        }

        if ($availableTickets < $ticketsToApply) {
            throw new \Exception('Você não possui tickets suficientes.');
        }

        return DB::transaction(function () use ($user, $raffle, $ticketsToApply, $walletTickets) {
            $remainingToApply = $ticketsToApply;
            $appliedTickets = [];

            foreach ($walletTickets as $walletTicket) {
                if ($remainingToApply <= 0) {
                    break;
                }

                $available = $walletTicket->total_tickets;

                if ($available > 0) {
                    $toDecrement = min($remainingToApply, $available);

                    // Buscar tickets disponíveis do pool
                    $poolTickets = Ticket::available()
                        ->inRandomOrder()
                        ->limit($toDecrement)
                        ->get();

                    if ($poolTickets->count() < $toDecrement) {
                        throw new \Exception('Tickets insuficientes no pool global');
                    }

                    // Decrementar do wallet
                    $walletTicket->decrement('total_tickets', $toDecrement);

                    // Criar registros em raffle_tickets
                    foreach ($poolTickets as $ticket) {
                        $raffleTicket = RaffleTicket::create([
                            'user_id' => $user->id,
                            'raffle_id' => $raffle->id,
                            'ticket_id' => $ticket->id,
                            'status' => RaffleTicket::STATUS_PENDING,
                        ]);

                        $appliedTickets[] = [
                            'uuid' => $raffleTicket->uuid,
                            'ticket_number' => $ticket->number,
                            'status' => $raffleTicket->status,
                            'created_at' => $raffleTicket->created_at,
                        ];
                    }

                    $remainingToApply -= $toDecrement;
                }
            }

            if ($remainingToApply > 0) {
                throw new \Exception('Não foi possível aplicar todos os tickets solicitados');
            }

            // Calcular tickets restantes do usuário
            $remainingTickets = $user->walletTickets()
                ->where('ticket_level', '>=', $raffle->min_ticket_level)
                ->where('status', 'active')
                ->sum('total_tickets');

            return [
                'applied_tickets' => $appliedTickets,
                'remaining_tickets' => $remainingTickets,
            ];
        });
    }

    /**
     * Cancela tickets aplicados em uma rifa
     *
     * @param  User  $user  Usuário
     * @param  Raffle  $raffle  Rifa
     * @param  array  $uuids  UUIDs dos tickets a cancelar
     */
    public function cancelTicketsFromRaffle(User $user, Raffle $raffle, array $uuids): array
    {
        return DB::transaction(function () use ($user, $raffle, $uuids) {
            $query = RaffleTicket::where('user_id', $user->id)
                ->where('raffle_id', $raffle->id)
                ->whereIn('uuid', $uuids)
                ->where('status', RaffleTicket::STATUS_PENDING);

            $raffleTickets = $query->get();

            if ($raffleTickets->isEmpty()) {
                throw new \Exception('Alguns tickets não puderam ser cancelados (já estão confirmados ou não pertencem a você).');
            }

            if ($raffleTickets->count() < count($uuids)) {
                throw new \Exception('Alguns tickets não puderam ser cancelados (já estão confirmados ou não pertencem a você).');
            }

            $canceledCount = 0;
            $returnedTickets = 0;

            // Agrupar por ticket_level para devolver aos wallets corretos
            $ticketsByLevel = [];

            foreach ($raffleTickets as $raffleTicket) {
                // Soft delete o raffle_ticket
                $raffleTicket->delete();
                $canceledCount++;

                // Identificar o nível do ticket (assumindo level 1 por padrão se não houver)
                // Na prática, deveria pegar do wallet original
                $level = 1; // TODO: Rastrear nível original

                if (! isset($ticketsByLevel[$level])) {
                    $ticketsByLevel[$level] = 0;
                }
                $ticketsByLevel[$level]++;
            }

            // Devolver tickets aos wallets
            foreach ($ticketsByLevel as $level => $count) {
                $walletTicket = $user->walletTickets()
                    ->where('ticket_level', $level)
                    ->where('status', 'active')
                    ->first();

                if ($walletTicket) {
                    $walletTicket->increment('total_tickets', $count);
                    $returnedTickets += $count;
                }
            }

            // Recalcular total de tickets no wallet
            $totalInWallet = $user->walletTickets()
                ->where('status', 'active')
                ->sum('total_tickets');

            return [
                'canceled_count' => $canceledCount,
                'returned_tickets' => $totalInWallet,
            ];
        });
    }

    /**
     * Lista tickets de um usuário em uma rifa
     */
    public function getUserTicketsInRaffle(User $user, Raffle $raffle): array
    {
        $raffleTickets = RaffleTicket::with('ticket')
            ->where('user_id', $user->id)
            ->where('raffle_id', $raffle->id)
            ->get();

        $byStatus = [
            'pending' => $raffleTickets->where('status', RaffleTicket::STATUS_PENDING)->count(),
            'confirmed' => $raffleTickets->where('status', RaffleTicket::STATUS_CONFIRMED)->count(),
            'winner' => $raffleTickets->where('status', RaffleTicket::STATUS_WINNER)->count(),
        ];

        return [
            'tickets' => $raffleTickets->map(function ($rt) {
                return [
                    'uuid' => $rt->uuid,
                    'ticket_number' => $rt->ticket->number,
                    'status' => $rt->status,
                    'created_at' => $rt->created_at,
                ];
            })->values()->toArray(),
            'total' => $raffleTickets->count(),
            'by_status' => $byStatus,
        ];
    }
}
