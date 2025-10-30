<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Services\Customer\CustomerPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerPlanController extends Controller
{
    protected CustomerPlanService $customerPlanService;

    public function __construct(CustomerPlanService $customerPlanService)
    {
        $this->customerPlanService = $customerPlanService;
    }

    /**
     * Listar todos os planos ativos
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [];

            if ($request->has('promotional')) {
                $filters['promotional'] = $request->boolean('promotional');
            }

            if ($request->has('min_price')) {
                $filters['min_price'] = $request->float('min_price');
            }

            if ($request->has('max_price')) {
                $filters['max_price'] = $request->float('max_price');
            }

            $filters['sort_by'] = $request->get('sort_by', 'price');
            $filters['sort_order'] = $request->get('sort_order', 'asc');

            $result = $this->customerPlanService->index($filters);

            return response()->json([
                'status' => 'success',
                'message' => __('app.plan.listed'),
                'data' => $result,
                'meta' => ['execution_time_ms' => round((microtime(true) - (microtime(true) - 0.1)) * 1000, 2)]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('app.plan.error_listing'),
                'errors' => ['plan' => $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Mostrar detalhes de um plano específico
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $plan = $this->customerPlanService->show($uuid);

            return response()->json([
                'status' => 'success',
                'message' => __('app.plan.found'),
                'data' => ['plan' => $plan],
                'meta' => ['execution_time_ms' => round((microtime(true) - (microtime(true) - 0.1)) * 1000, 2)]
            ], 200);

        } catch (\Exception $e) {
            $statusCode = $e->getMessage() === __('app.plan.not_found_or_inactive') ? 404 : 500;

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'errors' => ['plan' => $e->getMessage()]
            ], $statusCode);
        }
    }

    /**
     * Listar apenas planos promocionais
     */
    public function promotional(): JsonResponse
    {
        try {
            $result = $this->customerPlanService->promotional();

            return response()->json([
                'success' => true,
                'message' => 'Planos promocionais listados com sucesso',
                'data' => $result,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar planos promocionais',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Buscar planos por faixa de preço
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $searchParams = [];

            if ($request->has('search')) {
                $searchParams['search'] = $request->get('search');
            }

            if ($request->has('price_range')) {
                $searchParams['price_range'] = $request->get('price_range');
            }

            $result = $this->customerPlanService->search($searchParams);

            return response()->json([
                'success' => true,
                'message' => 'Busca realizada com sucesso',
                'data' => $result,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar planos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
