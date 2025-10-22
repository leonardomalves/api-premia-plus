<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Jobs\UserApplyToRaffleJob;
use App\Models\Raffle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerRaffleController extends Controller
{
    /**
     * Aplicar em uma rifa
     * 
     * POST /api/v1/customer/raffles/{uuid}/apply
     * 
     * Validações iniciais:
     * - Usuário autenticado
     * - Rifa existe e está ativa
     * - Quantidade é válida (>= min_tickets_required)
     * - Usuário tem saldo suficiente
     * - Usuário não aplicou ainda
     * 
     * Após validações, dispara job assíncrono para processar
     * 
     * @param Request $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function apply(Request $request, string $uuid): JsonResponse
    {
        // Validação do payload
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $quantity = $validated['quantity'];

        // Buscar rifa
        $raffle = Raffle::where('uuid', $uuid)->first();

        if (!$raffle) {
            return response()->json([
                'success' => false,
                'message' => 'Rifa não encontrada',
            ], 404);
        }

        // Validação 1: Rifa deve estar ativa
        if ($raffle->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Esta rifa não está disponível para aplicação',
                'raffle_status' => $raffle->status,
            ], 400);
        }

        // Validação 2: Quantidade mínima
        if ($quantity < $raffle->min_tickets_required) {
            return response()->json([
                'success' => false,
                'message' => "Quantidade mínima de tickets é {$raffle->min_tickets_required}",
                'min_required' => $raffle->min_tickets_required,
                'provided' => $quantity,
            ], 400);
        }

        // Validação 3: Usuário já aplicou?
        $alreadyApplied = $raffle->tickets()
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyApplied) {
            return response()->json([
                'success' => false,
                'message' => 'Você já aplicou nesta rifa',
                'raffle_id' => $raffle->id,
            ], 400);
        }

        // Validação 4: Saldo suficiente
        $totalCost = $quantity * $raffle->unit_ticket_value;
        $wallet = $user->wallet;

        if (!$wallet || $wallet->available_balance < $totalCost) {
            return response()->json([
                'success' => false,
                'message' => 'Saldo insuficiente',
                'required' => $totalCost,
                'available' => $wallet ? $wallet->available_balance : 0,
            ], 400);
        }

        // Validação 5: Tickets disponíveis no pool
        $availableTickets = $raffle->availableTicketsCount();
        
        if ($availableTickets < $quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Não há tickets suficientes disponíveis',
                'requested' => $quantity,
                'available' => $availableTickets,
            ], 400);
        }

        // Todas as validações passaram - Disparar Job
        UserApplyToRaffleJob::dispatch($user, $raffle, $quantity);

        return response()->json([
            'success' => true,
            'message' => 'Aplicação em processamento',
            'data' => [
                'raffle' => [
                    'id' => $raffle->id,
                    'uuid' => $raffle->uuid,
                    'title' => $raffle->title,
                ],
                'quantity' => $quantity,
                'total_cost' => $totalCost,
                'status' => 'processing',
                'note' => 'Sua aplicação está sendo processada. Você receberá os tickets em breve.',
            ],
        ], 202); // 202 Accepted - Processamento assíncrono
    }

    /**
     * Listar minhas aplicações em rifas
     * 
     * GET /api/v1/customer/raffles/my-applications
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function myApplications(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 15);

        // Buscar rifas em que o usuário aplicou
        $applications = Raffle::whereHas('tickets', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->with(['tickets' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->paginate($perPage);

        $data = $applications->map(function ($raffle) {
            $userTickets = $raffle->tickets;
            
            return [
                'raffle' => [
                    'id' => $raffle->id,
                    'uuid' => $raffle->uuid,
                    'title' => $raffle->title,
                    'status' => $raffle->status,
                    'draw_date' => $raffle->draw_date,
                    'unit_ticket_value' => (float) $raffle->unit_ticket_value,
                ],
                'tickets_count' => $userTickets->count(),
                'total_paid' => $userTickets->count() * $raffle->unit_ticket_value,
                'ticket_numbers' => $userTickets->pluck('ticket_number')->toArray(),
                'applied_at' => $userTickets->first()?->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'applications' => [
                'data' => $data,
                'current_page' => $applications->currentPage(),
                'per_page' => $applications->perPage(),
                'total' => $applications->total(),
                'last_page' => $applications->lastPage(),
            ],
        ], 200);
    }

    /**
     * Meus tickets em uma rifa específica
     * 
     * GET /api/v1/customer/raffles/{uuid}/my-tickets
     * 
     * @param Request $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function myTickets(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();

        $raffle = Raffle::where('uuid', $uuid)
            ->with(['tickets' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->first();

        if (!$raffle) {
            return response()->json([
                'message' => 'Rifa não encontrada',
            ], 404);
        }

        $userTickets = $raffle->tickets;

        if ($userTickets->isEmpty()) {
            return response()->json([
                'message' => 'Você não possui tickets nesta rifa',
            ], 404);
        }

        // Agrupar por status
        $ticketsByStatus = $userTickets->groupBy('status');

        return response()->json([
            'raffle' => [
                'id' => $raffle->id,
                'uuid' => $raffle->uuid,
                'title' => $raffle->title,
                'status' => $raffle->status,
                'draw_date' => $raffle->draw_date,
                'unit_ticket_value' => (float) $raffle->unit_ticket_value,
            ],
            'tickets' => [
                'total' => $userTickets->count(),
                'ticket_numbers' => $userTickets->pluck('ticket_number')->toArray(),
                'by_status' => [
                    'confirmed' => $ticketsByStatus->get('confirmed', collect())->count(),
                    'winner' => $ticketsByStatus->get('winner', collect())->count(),
                ],
                'total_paid' => $userTickets->count() * $raffle->unit_ticket_value,
            ],
        ], 200);
    }
}
