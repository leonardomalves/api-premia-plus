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
     * POST /customer/raffles/{uuid}/tickets
     */
    public function applyTickets(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $raffle = Raffle::where('uuid', $uuid)->firstOrFail();

            $result = $this->raffleTicketService->applyTicketsToRaffle(
                $request->user(),
                $raffle,
                $request->input('quantity')
            );

            return response()->json([
                'message' => 'Tickets aplicados com sucesso',
                'applied_tickets' => $result['applied_tickets'],
                'remaining_tickets' => $result['remaining_tickets'],
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Rifa não encontrada',
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
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
                'tickets' => $result['tickets'],
                'total' => $result['total'],
                'by_status' => $result['by_status'],
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Rifa não encontrada',
            ], 404);
        }
    }

    /**
     * Cancelar tickets aplicados em uma rifa
     * DELETE /customer/raffles/{uuid}/tickets
     */
    public function cancelTickets(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'raffle_ticket_uuids' => 'required|array',
            'raffle_ticket_uuids.*' => 'string',
        ]);

        try {
            $raffle = Raffle::where('uuid', $uuid)->firstOrFail();

            $result = $this->raffleTicketService->cancelTicketsFromRaffle(
                $request->user(),
                $raffle,
                $request->input('raffle_ticket_uuids')
            );

            if ($result['canceled_count'] === 0) {
                return response()->json([
                    'message' => 'Alguns tickets não puderam ser cancelados (já estão confirmados ou não pertencem a você).',
                ], 400);
            }

            return response()->json([
                'message' => 'Tickets cancelados com sucesso',
                'canceled_count' => $result['canceled_count'],
                'returned_tickets' => $result['returned_tickets'],
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Rifa não encontrada',
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
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
                'raffles' => [
                    'data' => $raffles->items(),
                    'current_page' => $raffles->currentPage(),
                    'per_page' => $raffles->perPage(),
                    'total' => $raffles->total(),
                    'last_page' => $raffles->lastPage(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao listar rifas',
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
                'raffle' => $raffle,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Rifa não encontrada ou inativa',
            ], 404);
        }
    }
}
