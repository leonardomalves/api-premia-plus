<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Raffle;
use App\Services\Customer\RaffleTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerRaffleTicketController extends Controller
{
    protected RaffleTicketService $raffleTicketService;

    public function __construct(RaffleTicketService $raffleTicketService)
    {
        $this->raffleTicketService = $raffleTicketService;
    }

    /**
     * Aplicar tickets em uma rifa
     * POST /customer/raffles/{uuid}/apply-tickets
     */
    public function applyTickets(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'quantity' => 'sometimes|integer|min:1',
        ]);

        try {
            $raffle = Raffle::where('uuid', $uuid)
                ->where('status', Raffle::STATUS_ACTIVE)
                ->firstOrFail();

            $result = $this->raffleTicketService->applyTicketsToRaffle(
                $request->user(),
                $raffle,
                $request->input('quantity')
            );

            return response()->json($result, 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rifa não encontrada ou inativa',
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Listar tickets do usuário em uma rifa
     * GET /customer/raffles/{uuid}/my-tickets
     */
    public function myTickets(Request $request, string $uuid): JsonResponse
    {
        try {
            $raffle = Raffle::where('uuid', $uuid)->firstOrFail();

            $result = $this->raffleTicketService->getUserTicketsInRaffle(
                $request->user(),
                $raffle
            );

            return response()->json([
                'success' => true,
                'message' => 'Tickets listados com sucesso',
                'data' => $result,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rifa não encontrada',
            ], 404);
        }
    }

    /**
     * Cancelar tickets aplicados em uma rifa
     * DELETE /customer/raffles/{uuid}/cancel-tickets
     */
    public function cancelTickets(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'ticket_ids' => 'sometimes|array',
            'ticket_ids.*' => 'integer|exists:raffle_tickets,id',
        ]);

        try {
            $raffle = Raffle::where('uuid', $uuid)->firstOrFail();

            $result = $this->raffleTicketService->cancelTicketsFromRaffle(
                $request->user(),
                $raffle,
                $request->input('ticket_ids')
            );

            return response()->json($result, 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rifa não encontrada',
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Listar rifas disponíveis
     * GET /customer/raffles
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $raffles = Raffle::where('status', Raffle::STATUS_ACTIVE)
                ->orderBy('draw_date', 'asc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'message' => 'Rifas listadas com sucesso',
                'data' => [
                    'raffles' => $raffles->items(),
                    'pagination' => [
                        'current_page' => $raffles->currentPage(),
                        'per_page' => $raffles->perPage(),
                        'total' => $raffles->total(),
                        'last_page' => $raffles->lastPage(),
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar rifas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detalhes de uma rifa
     * GET /customer/raffles/{uuid}
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        try {
            $raffle = Raffle::where('uuid', $uuid)
                ->where('status', Raffle::STATUS_ACTIVE)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'message' => 'Rifa encontrada com sucesso',
                'data' => [
                    'raffle' => $raffle,
                ],
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rifa não encontrada ou inativa',
            ], 404);
        }
    }
}
