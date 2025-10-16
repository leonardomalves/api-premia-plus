<?php

namespace App\Http\Controllers\Api\Administrator;

use App\Http\Controllers\Controller;
use App\Models\Raffle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdministratorRaffleController extends Controller
{
    /**
     * Listar todos os raffles (admin)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Raffle::with('creator');

            // Filtros
            if ($request->has('status')) {
                $query->where('status', $request->get('status'));
            }

            if ($request->has('min_prize')) {
                $query->where('prize_value', '>=', $request->float('min_prize'));
            }

            if ($request->has('max_prize')) {
                $query->where('prize_value', '<=', $request->float('max_prize'));
            }

            // Busca por título/descrição
            if ($request->has('search')) {
                $searchTerm = $request->get('search');
                $query->where(function($q) use ($searchTerm) {
                    $q->where('title', 'like', "%{$searchTerm}%")
                      ->orWhere('description', 'like', "%{$searchTerm}%");
                });
            }

            // Ordenação
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            if (in_array($sortBy, ['created_at', 'title', 'prize_value', 'status'])) {
                $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
            }

            $raffles = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'raffles' => $raffles,
                'filters' => [
                    'status' => $request->get('status'),
                    'search' => $request->get('search'),
                    'min_prize' => $request->get('min_prize'),
                    'max_prize' => $request->get('max_prize'),
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao listar raffles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibir um raffle específico (admin)
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $raffle = Raffle::with('creator')->where('uuid', $uuid)->firstOrFail();

            return response()->json([
                'raffle' => $raffle
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Raffle não encontrado'
            ], 404);
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
                'min_ticket_level' => 'required|integer|min:0|max:100',
                'max_tickets_per_user' => 'required|integer|min:1|max:1000',
                'status' => 'sometimes|in:pending,active,inactive,cancelled',
                'notes' => 'nullable|string|max:2000',
                'liquidity_ratio' => 'numeric|min:0|max:100',
                'liquid_value' => 'numeric|min:0|max:999999.99'
            ]);

            $validated['created_by'] = auth()->id();
            $validated['status'] = $validated['status'] ?? 'draft';

            $raffle = Raffle::create($validated);

            return response()->json([
                'message' => 'Raffle criado com sucesso',
                'raffle' => $raffle->load('creator')
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao criar raffle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar raffle (admin)
     */
    public function update(Request $request, string $uuid): JsonResponse
    {
        try {
            $raffle = Raffle::where('uuid', $uuid)->firstOrFail();

            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:255|unique:raffles,title,' . $raffle->id,
                'description' => 'sometimes|required|string|max:1000',
                'prize_value' => 'sometimes|required|numeric|min:0.01|max:999999.99',
                'operation_cost' => 'sometimes|required|numeric|min:0|max:999999.99',
                'unit_ticket_value' => 'sometimes|required|numeric|min:0.01|max:999.99',
                'tickets_required' => 'sometimes|required|integer|min:1|max:1000000',
                'min_ticket_level' => 'sometimes|required|integer|min:0|max:100',
                'max_tickets_per_user' => 'sometimes|required|integer|min:1|max:1000',
                'status' => 'sometimes|in:draft,active,inactive,cancelled',
                'notes' => 'nullable|string|max:2000'
            ], [
                'title.unique' => 'Já existe um raffle com este título',
                'description.required' => 'A descrição é obrigatória quando fornecida',
                'prize_value.min' => 'O valor do prêmio deve ser maior que zero',
                'operation_cost.min' => 'O custo operacional não pode ser negativo',
                'unit_ticket_value.min' => 'O valor unitário do ticket deve ser maior que zero',
                'tickets_required.min' => 'Deve haver pelo menos 1 ticket',
                'min_ticket_level.min' => 'O nível mínimo não pode ser negativo',
                'max_tickets_per_user.min' => 'Cada usuário deve poder comprar pelo menos 1 ticket',
                'status.in' => 'Status deve ser: draft, active, inactive ou cancelled'
            ]);

            $raffle->update($validated);

            return response()->json([
                'message' => 'Raffle atualizado com sucesso',
                'raffle' => $raffle->load('creator')
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao atualizar raffle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remover raffle (soft delete) (admin)
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $raffle = Raffle::where('uuid', $uuid)->firstOrFail();
            
            $raffle->delete();

            return response()->json([
                'message' => 'Raffle removido com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao remover raffle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restaurar raffle deletado (admin)
     */
    public function restore(string $uuid): JsonResponse
    {
        try {
            $raffle = Raffle::withTrashed()->where('uuid', $uuid)->firstOrFail();
            
            $raffle->restore();

            return response()->json([
                'message' => 'Raffle restaurado com sucesso',
                'raffle' => $raffle->load('creator')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao restaurar raffle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Alternar status do raffle (admin)
     */
    public function toggleStatus(string $uuid): JsonResponse
    {
        try {
            $raffle = Raffle::where('uuid', $uuid)->firstOrFail();
            
            $newStatus = $raffle->status === 'active' ? 'inactive' : 'active';
            $raffle->update(['status' => $newStatus]);

            return response()->json([
                'message' => "Status alterado para {$newStatus}",
                'raffle' => $raffle->load('creator')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao alterar status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Estatísticas dos raffles (admin)
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_raffles' => Raffle::count(),
                'active_raffles' => Raffle::where('status', 'active')->count(),
                'draft_raffles' => Raffle::where('status', 'draft')->count(),
                'inactive_raffles' => Raffle::where('status', 'inactive')->count(),
                'cancelled_raffles' => Raffle::where('status', 'cancelled')->count(),
                'total_prize_value' => Raffle::where('status', 'active')->sum('prize_value'),
                'avg_prize_value' => Raffle::where('status', 'active')->avg('prize_value'),
                'recent_raffles' => Raffle::with('creator')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
            ];

            return response()->json([
                'statistics' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao gerar estatísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}