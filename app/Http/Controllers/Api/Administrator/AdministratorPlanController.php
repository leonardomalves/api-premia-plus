<?php

namespace App\Http\Controllers\Api\Administrator;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdministratorPlanController extends Controller
{
    /**
     * Listar todos os planos (admin)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Plan::query();

            // Filtros
            if ($request->has('status')) {
                $query->where('status', $request->get('status'));
            }

            if ($request->has('promotional')) {
                $query->where('is_promotional', $request->boolean('promotional'));
            }

            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->float('min_price'));
            }

            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->float('max_price'));
            }

            // Busca por nome
            if ($request->has('search')) {
                $searchTerm = $request->get('search');
                $query->where(function($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                      ->orWhere('description', 'like', "%{$searchTerm}%");
                });
            }

            // Ordenação
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            $allowedSorts = ['name', 'price', 'status', 'created_at', 'updated_at'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Paginação
            $perPage = $request->get('per_page', 15);
            $plans = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Planos listados com sucesso',
                'data' => [
                    'plans' => $plans->items(),
                    'pagination' => [
                        'current_page' => $plans->currentPage(),
                        'per_page' => $plans->perPage(),
                        'total' => $plans->total(),
                        'last_page' => $plans->lastPage(),
                        'from' => $plans->firstItem(),
                        'to' => $plans->lastItem(),
                    ],
                    'filters' => $request->only(['status', 'promotional', 'min_price', 'max_price', 'search', 'sort_by', 'sort_order'])
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar planos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar detalhes de um plano (admin)
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $plan = Plan::where('uuid', $uuid)->first();

            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plano não encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Plano encontrado com sucesso',
                'data' => [
                    'plan' => $plan
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar plano',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Criar novo plano
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:plans,name',
                'description' => 'required|string|max:1000',
                'price' => 'required|numeric|min:0',
                'grant_tickets' => 'required|integer|min:0',
                'status' => ['required', Rule::in(['active', 'inactive', 'archived'])],
                'commission_level_1' => 'required|numeric|min:0|max:100',
                'commission_level_2' => 'required|numeric|min:0|max:100',
                'commission_level_3' => 'required|numeric|min:0|max:100',
                'is_promotional' => 'boolean',
                'overlap' => 'required|integer|min:0',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after:start_date',
            ]);

            $plan = Plan::create([
                'uuid' => Str::uuid(),
                ...$validated
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Plano criado com sucesso',
                'data' => [
                    'plan' => $plan
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar plano',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar plano
     */
    public function update(Request $request, string $uuid): JsonResponse
    {
        try {
            $plan = Plan::where('uuid', $uuid)->first();

            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plano não encontrado'
                ], 404);
            }

            $validated = $request->validate([
                'name' => ['sometimes', 'string', 'max:255', Rule::unique('plans', 'name')->ignore($plan->id)],
                'description' => 'sometimes|string|max:1000',
                'price' => 'sometimes|numeric|min:0',
                'grant_tickets' => 'sometimes|integer|min:0',
                'status' => ['sometimes', Rule::in(['active', 'inactive', 'archived'])],
                'commission_level_1' => 'sometimes|numeric|min:0|max:100',
                'commission_level_2' => 'sometimes|numeric|min:0|max:100',
                'commission_level_3' => 'sometimes|numeric|min:0|max:100',
                'is_promotional' => 'sometimes|boolean',
                'overlap' => 'sometimes|integer|min:0',
                'start_date' => 'sometimes|date',
                'end_date' => 'nullable|date|after:start_date',
            ]);

            $plan->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Plano atualizado com sucesso',
                'data' => [
                    'plan' => $plan->fresh()
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar plano',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deletar plano
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $plan = Plan::where('uuid', $uuid)->first();

            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plano não encontrado'
                ], 404);
            }

            $plan->delete();

            return response()->json([
                'success' => true,
                'message' => 'Plano deletado com sucesso'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao deletar plano',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ativar/Desativar plano
     */
    public function toggleStatus(string $uuid): JsonResponse
    {
        try {
            $plan = Plan::where('uuid', $uuid)->first();

            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plano não encontrado'
                ], 404);
            }

            $newStatus = $plan->status === 'active' ? 'inactive' : 'active';
            $plan->update(['status' => $newStatus]);

            $statusText = $newStatus === 'active' ? 'ativado' : 'desativado';
            
            return response()->json([
                'success' => true,
                'message' => "Plano {$statusText} com sucesso",
                'data' => [
                    'plan' => $plan->fresh(),
                    'new_status' => $newStatus
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar status do plano',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Estatísticas dos planos
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_plans' => Plan::count(),
                'active_plans' => Plan::where('status', 'active')->count(),
                'inactive_plans' => Plan::where('status', 'inactive')->count(),
                'promotional_plans' => Plan::where('is_promotional', true)->count(),
                'average_price' => Plan::avg('price'),
                'min_price' => Plan::min('price'),
                'max_price' => Plan::max('price'),
                'total_tickets' => Plan::sum('grant_tickets'),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Estatísticas dos planos',
                'data' => [
                    'statistics' => $stats
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar estatísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
