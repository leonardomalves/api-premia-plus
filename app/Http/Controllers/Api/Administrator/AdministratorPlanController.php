<?php

namespace App\Http\Controllers\Api\Administrator;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\Administrator\PlanManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
            $filters = [
                'status' => $request->get('status'),
                'promotional' => $request->boolean('promotional'),
                'min_price' => $request->float('min_price'),
                'max_price' => $request->float('max_price'),
                'search' => $request->get('search'),
                'sort_by' => $request->get('sort_by', 'created_at'),
                'sort_order' => $request->get('sort_order', 'desc'),
            ];

            $perPage = $request->get('per_page', 15);
            $result = $this->planService->listPlans($filters, $perPage);

            return response()->json([
                'success' => true,
                'message' => 'Planos listados com sucesso',
                'data' => $result
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
     * Buscar plano específico por UUID (admin)
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $plan = $this->planService->findPlanByUuid($uuid);

            return response()->json([
                'success' => true,
                'message' => 'Plano encontrado com sucesso',
                'data' => $plan
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar plano',
                'error' => $e->getMessage()
            ], 404);
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
            
            $plan = $this->planService->createPlan($validated);

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
            $plan = $this->planService->findPlanByUuid($uuid);
            
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
            
            $plan = $this->planService->updatePlan($plan, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Plano atualizado com sucesso',
                'data' => [
                    'plan' => $plan
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
            $this->planService->deletePlan($uuid);

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
            $result = $this->planService->togglePlanStatus($uuid);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'plan' => $result['plan'],
                    'new_status' => $result['new_status']
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
            $stats = $this->planService->getPlanStatistics();

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
