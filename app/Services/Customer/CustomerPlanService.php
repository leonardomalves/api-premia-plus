<?php

declare(strict_types=1);

namespace App\Services\Customer;

use App\Models\Plan;

class CustomerPlanService
{
    /**
     * Listar todos os planos ativos
     */
    public function index(array $filters = []): array
    {
        $query = Plan::where('status', 'active');

        // Filtro por tipo (promocional ou não)
        if (isset($filters['promotional'])) {
            $query->where('is_promotional', $filters['promotional']);
        }

        // Filtro por preço mínimo
        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        // Filtro por preço máximo
        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        // Ordenação
        $sortBy = $filters['sort_by'] ?? 'price';
        $sortOrder = $filters['sort_order'] ?? 'asc';

        $allowedSorts = ['price', 'name', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $plans = $query->get();

        return [
            'plans' => $plans,
            'total' => $plans->count(),
            'filters' => [
                'promotional' => $filters['promotional'] ?? null,
                'min_price' => $filters['min_price'] ?? null,
                'max_price' => $filters['max_price'] ?? null,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
        ];
    }

    /**
     * Mostrar detalhes de um plano específico
     */
    public function show(string $uuid): Plan
    {
        $plan = Plan::where('uuid', $uuid)
            ->where('status', 'active')
            ->first();

        if (! $plan) {
            throw new \Exception(__('app.plan.not_found_or_inactive'));
        }

        return $plan;
    }

    /**
     * Listar apenas planos promocionais
     */
    public function promotional(): array
    {
        $plans = Plan::where('status', 'active')
            ->where('is_promotional', true)
            ->orderBy('price', 'asc')
            ->get();

        return [
            'plans' => $plans,
            'total' => $plans->count(),
        ];
    }

    /**
     * Buscar planos por faixa de preço
     */
    public function search(array $searchParams = []): array
    {
        $query = Plan::where('status', 'active');

        // Busca por nome ou descrição
        if (isset($searchParams['search'])) {
            $searchTerm = $searchParams['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        // Filtro por faixa de preço
        if (isset($searchParams['price_range'])) {
            $priceRange = $searchParams['price_range'];
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

        return [
            'plans' => $plans,
            'total' => $plans->count(),
            'search_term' => $searchParams['search'] ?? null,
            'price_range' => $searchParams['price_range'] ?? null,
        ];
    }
}
