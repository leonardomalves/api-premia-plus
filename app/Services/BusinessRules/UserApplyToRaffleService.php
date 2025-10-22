<?php

namespace App\Services\BusinessRules;

use App\Models\Raffle;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Service responsible for applying users to raffles
 *
 * This service handles the process of purchasing raffle tickets,
 * debiting the user's wallet, and creating financial statements.
 */
class UserApplyToRaffleService
{
    /**
     * Apply a user to a raffle by purchasing tickets
     *
     * @param User $user The user applying to the raffle
     * @param Raffle $raffle The raffle to apply to
     * @param int $ticketCount Number of tickets to purchase
     * @return array Result of the application
     */
    public function applyUserToRaffle(User $user, Raffle $raffle, int $ticketCount): array
    {
        // Validações iniciais
        $validation = $this->validateApplication($user, $raffle, $ticketCount);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['message'],
                'user_id' => $user->id,
                'raffle_id' => $raffle->id,
            ];
        }

        $wallet = $user->wallet;
        $totalCost = $ticketCount * $raffle->unit_ticket_value;

        DB::beginTransaction();

        try {
            $startTime = microtime(true);

            // Buscar tickets disponíveis usando query otimizada
            $selectedTickets = $this->getAvailableTickets($ticketCount);

            if ($selectedTickets->count() < $ticketCount) {
                throw new \Exception("Tickets insuficientes no pool. Disponíveis: {$selectedTickets->count()}, Necessário: {$ticketCount}");
            }

            // Debitar saldo da wallet
            $wallet->decrement('balance', $totalCost);

            // Criar entrada de débito no FinancialStatement
            $this->createFinancialStatement($user, $raffle, $ticketCount, $totalCost);

            // Criar registros de raffle_tickets em batch
            $ticketNumbers = $this->createRaffleTickets($user, $raffle, $selectedTickets);

            DB::commit();

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('User successfully applied to raffle', [
                'user_id' => $user->id,
                'raffle_id' => $raffle->id,
                'raffle_uuid' => $raffle->uuid,
                'tickets_count' => $ticketCount,
                'total_cost' => $totalCost,
                'duration_ms' => $duration,
            ]);

            return [
                'success' => true,
                'message' => 'Aplicação realizada com sucesso',
                'user_id' => $user->id,
                'raffle_id' => $raffle->id,
                'raffle_title' => $raffle->title,
                'tickets_count' => $ticketCount,
                'total_cost' => $totalCost,
                'ticket_numbers' => $ticketNumbers,
                'remaining_balance' => $wallet->fresh()->balance,
                'duration_ms' => $duration,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to apply user to raffle', [
                'user_id' => $user->id,
                'raffle_id' => $raffle->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao processar aplicação: ' . $e->getMessage(),
                'user_id' => $user->id,
                'raffle_id' => $raffle->id,
            ];
        }
    }

    /**
     * Validate the application before processing
     *
     * @param User $user
     * @param Raffle $raffle
     * @param int $ticketCount
     * @return array
     */
    protected function validateApplication(User $user, Raffle $raffle, int $ticketCount): array
    {
        // Verificar se a rifa está ativa
        if ($raffle->status !== 'active') {
            return [
                'valid' => false,
                'message' => 'Esta rifa não está ativa',
            ];
        }

        // Verificar se o usuário tem wallet
        if (!$user->wallet) {
            return [
                'valid' => false,
                'message' => 'Usuário não possui carteira',
            ];
        }

        // Verificar quantidade mínima de tickets
        if ($ticketCount < $raffle->min_tickets_required) {
            return [
                'valid' => false,
                'message' => "Quantidade mínima de tickets: {$raffle->min_tickets_required}",
            ];
        }

        // Verificar se o usuário já aplicou nesta rifa
        $alreadyApplied = DB::table('raffle_tickets')
            ->where('user_id', $user->id)
            ->where('raffle_id', $raffle->id)
            ->exists();

        if ($alreadyApplied) {
            return [
                'valid' => false,
                'message' => 'Usuário já aplicou nesta rifa',
            ];
        }

        // Calcular custo total
        $totalCost = $ticketCount * $raffle->unit_ticket_value;

        // Verificar se tem saldo suficiente
        if ($user->wallet->balance < $totalCost) {
            return [
                'valid' => false,
                'message' => sprintf(
                    'Saldo insuficiente. Necessário: R$ %.2f, Disponível: R$ %.2f',
                    $totalCost,
                    $user->wallet->balance
                ),
            ];
        }

        return ['valid' => true];
    }

    /**
     * Get available tickets from the pool
     *
     * @param int $limit Number of tickets to retrieve
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getAvailableTickets(int $limit)
    {
        return Ticket::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('raffle_tickets')
                ->whereColumn('raffle_tickets.ticket_id', 'tickets.id');
        })
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * Create financial statement entry for the debit
     *
     * @param User $user
     * @param Raffle $raffle
     * @param int $ticketCount
     * @param float $totalCost
     * @return void
     */
    protected function createFinancialStatement(User $user, Raffle $raffle, int $ticketCount, float $totalCost): void
    {
        $now = now();
        $unitValue = $totalCost / $ticketCount;

        DB::table('financial_statements')->insert([
            'uuid' => Str::uuid(),
            'user_id' => $user->id,
            'correlation_id' => $raffle->uuid,
            'amount' => $totalCost,
            'type' => 'debit',
            'description' => sprintf(
                'Débito referente à aplicação em rifa - %s (%d ticket%s x R$ %.2f)',
                $raffle->title,
                $ticketCount,
                $ticketCount > 1 ? 's' : '',
                $unitValue
            ),
            'status' => 'completed',
            'origin' => 'raffle',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Create raffle_tickets entries in batch
     *
     * @param User $user
     * @param Raffle $raffle
     * @param \Illuminate\Database\Eloquent\Collection $tickets
     * @return array Array of ticket numbers
     */
    protected function createRaffleTickets(User $user, Raffle $raffle, $tickets): array
    {
        $now = now();
        $raffleTicketsData = [];
        $ticketNumbers = [];

        foreach ($tickets as $ticket) {
            $raffleTicketsData[] = [
                'uuid' => Str::uuid(),
                'user_id' => $user->id,
                'raffle_id' => $raffle->id,
                'ticket_id' => $ticket->id,
                'status' => 'confirmed',
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $ticketNumbers[] = $ticket->number;
        }

        // Batch insert
        DB::table('raffle_tickets')->insert($raffleTicketsData);

        return $ticketNumbers;
    }
}