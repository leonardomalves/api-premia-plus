<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerPlanController extends Controller
{
    /**
     * Listar todos os planos ativos
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Plan::where('status', 'active');

            // Filtro por tipo (promocional ou não)
            if ($request->has('promotional')) {
                $query->where('is_promotional', $request->boolean('promotional'));
            }

            // Filtro por preço mínimo
            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->float('min_price'));
            }

            // Filtro por preço máximo
            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->float('max_price'));
            }

            // Ordenação
            $sortBy = $request->get('sort_by', 'price');
            $sortOrder = $request->get('sort_order', 'asc');
            
            $allowedSorts = ['price', 'name', 'created_at'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            }

            $plans = $query->get();

            return response()->json([
                'success' => true,
                'message' => 'Planos listados com sucesso',
                'data' => [
                    'plans' => $plans,
                    'total' => $plans->count(),
                    'filters' => [
                        'promotional' => $request->get('promotional'),
                        'min_price' => $request->get('min_price'),
                        'max_price' => $request->get('max_price'),
                        'sort_by' => $sortBy,
                        'sort_order' => $sortOrder,
                    ]
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
     * Mostrar detalhes de um plano específico
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $plan = Plan::where('uuid', $uuid)
                ->where('status', 'active')
                ->first();

            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plano não encontrado ou inativo'
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
     * Listar apenas planos promocionais
     */
    public function promotional(): JsonResponse
    {
        try {
            $plans = Plan::where('status', 'active')
                ->where('is_promotional', true)
                ->orderBy('price', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Planos promocionais listados com sucesso',
                'data' => [
                    'plans' => $plans,
                    'total' => $plans->count()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar planos promocionais',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar planos por faixa de preço
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = Plan::where('status', 'active');

            // Busca por nome ou descrição
            if ($request->has('search')) {
                $searchTerm = $request->get('search');
                $query->where(function($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                      ->orWhere('description', 'like', "%{$searchTerm}%");
                });
            }

            // Filtro por faixa de preço
            if ($request->has('price_range')) {
                $priceRange = $request->get('price_range');
                switch ($priceRange) {
                    case 'low':
                        $query->where('price', '<=', 150);
                        break;
                    case 'medium':
                        $query->whereBetween('price', [150, 300]);
                        break;
                    case 'high':
                        $query->where('price', '>', 300);
                        break;
                }
            }

            $plans = $query->orderBy('price', 'asc')->get();

            return response()->json([
                'success' => true,
                'message' => 'Busca realizada com sucesso',
                'data' => [
                    'plans' => $plans,
                    'total' => $plans->count(),
                    'search_term' => $request->get('search'),
                    'price_range' => $request->get('price_range')
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar planos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
