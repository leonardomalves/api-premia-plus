<?php

namespace App\Http\Controllers\Api\Administrator;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\Administrator\PlanManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdministratorPlanController extends Controller
{
    protected PlanManagementService $planService;

    public function __construct(PlanManagementService $planService)
    {
        $this->planService = $planService;
    }

    /**
     * Listar todos os planos (admin)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [];

            if ($request->has('status') && ! empty($request->get('status'))) {
                $filters['status'] = $request->get('status');
            }

            if ($request->has('promotional')) {
                $filters['promotional'] = $request->boolean('promotional');
            }

            if ($request->has('min_price') && $request->get('min_price') !== null && $request->get('min_price') !== '') {
                $filters['min_price'] = $request->float('min_price');
            }

            if ($request->has('max_price') && $request->get('max_price') !== null && $request->get('max_price') !== '') {
                $filters['max_price'] = $request->float('max_price');
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
            $result = $this->planService->listPlans($filters, $perPage);

            return response()->json([
                'success' => true,
                'message' => 'Planos listados com sucesso',
                'data' => $result,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar planos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Buscar plano específico por UUID (admin)
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $plan = $this->planService->findPlanByUuid($uuid);

            return response()->json([
                'success' => true,
                'message' => 'Plano encontrado com sucesso',
                'data' => [
                    'plan' => $plan,
                ],
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Plano não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar plano',
                'error' => $e->getMessage(),
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
                'status' => ['required', Rule::in(['active', 'inactive', 'archived'])],
                'commission_level_1' => 'required|numeric|min:0|max:100',
                'commission_level_2' => 'required|numeric|min:0|max:100',
                'commission_level_3' => 'required|numeric|min:0|max:100',
                'is_promotional' => 'boolean',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after:start_date',
            ]);

            $plan = $this->planService->createPlan($validated);

            return response()->json([
                'success' => true,
                'message' => 'Plano criado com sucesso',
                'data' => [
                    'plan' => $plan,
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar plano',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Atualizar plano
     */
    public function update(Request $request, string $uuid): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => ['sometimes', 'string', 'max:255', Rule::unique('plans', 'name')->ignore(Plan::where('uuid', $uuid)->first()?->id)],
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

            $plan = $this->planService->updatePlan($uuid, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Plano atualizado com sucesso',
                'data' => [
                    'plan' => $plan,
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Plano não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar plano',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deletar plano
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $this->planService->deletePlan($uuid);

            return response()->json([
                'success' => true,
                'message' => 'Plano deletado com sucesso',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Plano não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao deletar plano',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ativar/Desativar plano
     */
    public function toggleStatus(string $uuid): JsonResponse
    {
        try {
            $plan = $this->planService->toggleStatus($uuid);

            $message = $plan->status === 'active' ? 'Plano ativado com sucesso' : 'Plano desativado com sucesso';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'plan' => $plan,
                    'new_status' => $plan->status,
                ],
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Plano não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar status do plano',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Estatísticas dos planos
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->planService->getPlanStatistics();

            return response()->json([
                'success' => true,
                'message' => 'Estatísticas dos planos',
                'data' => [
                    'statistics' => $stats,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar estatísticas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
