<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Administrator;

use App\Http\Controllers\Controller;
use App\Services\Administrator\RaffleManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdministratorRaffleController extends Controller
{
    protected RaffleManagementService $raffleService;

    public function __construct(RaffleManagementService $raffleService)
    {
        $this->raffleService = $raffleService;
    }

    /**
     * Listar todos os raffles (admin)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [];

            if ($request->has('status') && ! empty($request->get('status'))) {
                $filters['status'] = $request->get('status');
            }

            if ($request->has('min_prize') && $request->get('min_prize') !== null && $request->get('min_prize') !== '') {
                $filters['min_prize'] = $request->float('min_prize');
            }

            if ($request->has('max_prize') && $request->get('max_prize') !== null && $request->get('max_prize') !== '') {
                $filters['max_prize'] = $request->float('max_prize');
            }

            if ($request->has('search') && ! empty($request->get('search'))) {
                $filters['search'] = $request->get('search');
            }

            if ($request->has('sort_by') && ! empty($request->get('sort_by'))) {
                $filters['sort_by'] = $request->get('sort_by');
            } else {
                $filters['sort_by'] = 'created_at';
            }

            if ($request->has('sort_order') && ! empty($request->get('sort_order'))) {
                $filters['sort_order'] = $request->get('sort_order');
            } else {
                $filters['sort_order'] = 'desc';
            }

            $perPage = $request->get('per_page', 15);
            $result = $this->raffleService->listRaffles($filters, $perPage);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao listar raffles',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exibir um raffle específico (admin)
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $raffle = $this->raffleService->findRaffleByUuid($uuid);

            return response()->json([
                'raffle' => $raffle,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Raffle não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar raffle',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Criar novo raffle (admin)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255|unique:raffles,title',
                'description' => 'required|string|max:1000',
                'prize_value' => 'required|numeric|min:0.01|max:999999.99',
                'operation_cost' => 'required|numeric|min:0|max:999999.99',
                'unit_ticket_value' => 'required|numeric|min:0.01|max:999.99',
                'tickets_required' => 'required|integer|min:1|max:1000000',
                'min_ticket_level' => 'required|integer|min:1|max:100',
                'max_tickets_per_user' => 'required|integer|min:1|max:1000',
                'status' => 'sometimes|in:pending,active,inactive,cancelled',
                'notes' => 'nullable|string|max:2000',
                'liquidity_ratio' => 'numeric|min:0|max:100',
                'liquid_value' => 'numeric|min:0|max:999999.99',
            ]);

            $validated['created_by'] = $request->user()->id;
            $raffle = $this->raffleService->createRaffle($validated);

            return response()->json([
                'message' => 'Raffle criado com sucesso',
                'raffle' => $raffle,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao criar raffle',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Atualizar raffle (admin)
     */
    public function update(Request $request, string $uuid): JsonResponse
    {
        try {
            $raffle = $this->raffleService->findRaffleByUuid($uuid);

            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:255|unique:raffles,title,'.$raffle->id,
                'description' => 'sometimes|required|string|max:1000',
                'prize_value' => 'sometimes|required|numeric|min:0.01|max:999999.99',
                'operation_cost' => 'sometimes|required|numeric|min:0|max:999999.99',
                'unit_ticket_value' => 'sometimes|required|numeric|min:0.01|max:999.99',
                'tickets_required' => 'sometimes|required|integer|min:1|max:1000000',
                'min_ticket_level' => 'sometimes|required|integer|min:0|max:100',
                'max_tickets_per_user' => 'sometimes|required|integer|min:1|max:1000',
                'status' => 'sometimes|in:pending,active,inactive,cancelled',
                'notes' => 'nullable|string|max:2000',
            ]);

            $updatedRaffle = $this->raffleService->updateRaffle($raffle, $validated);

            return response()->json([
                'message' => 'Raffle atualizado com sucesso',
                'raffle' => $updatedRaffle,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Raffle não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao atualizar raffle',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remover raffle (soft delete) (admin)
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->raffleService->deleteRaffle($uuid);

            return response()->json([
                'message' => 'Raffle removido com sucesso',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Raffle não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao remover raffle',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restaurar raffle deletado (admin)
     */
    public function restore(string $uuid): JsonResponse
    {
        try {
            $raffle = $this->raffleService->restoreRaffle($uuid);

            return response()->json([
                'message' => 'Raffle restaurado com sucesso',
                'raffle' => $raffle,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Raffle não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao restaurar raffle',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Alternar status do raffle (admin)
     */
    public function toggleStatus(string $uuid): JsonResponse
    {
        try {
            $result = $this->raffleService->toggleRaffleStatus($uuid);

            return response()->json([
                'message' => $result['message'],
                'raffle' => $result['raffle'],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Raffle não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao alterar status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Estatísticas dos raffles (admin)
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->raffleService->getRaffleStatistics();

            return response()->json([
                'statistics' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao gerar estatísticas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
