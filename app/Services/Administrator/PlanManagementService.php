<?php

namespace App\Services\Administrator;

use App\Models\Plan;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class PlanManagementService
{
    /**
     * Listar planos com filtros e paginação
     */
    public function listPlans(array $filters = [], int $perPage = 15): array
    {
        $query = Plan::query();

        // Aplicar filtros
        if (isset($filters['status']) && !empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['promotional'])) {
            $query->where('is_promotional', $filters['promotional']);
        }

        if (isset($filters['min_price']) && is_numeric($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price']) && is_numeric($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        // Busca por nome e descrição
        if (isset($filters['search']) && !empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        // Ordenação
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        
        $allowedSorts = ['name', 'price', 'status', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $plans = $query->paginate($perPage);

        return [
            'plans' => $plans->items(),
            'pagination' => [
                'current_page' => $plans->currentPage(),
                'per_page' => $plans->perPage(),
                'total' => $plans->total(),
                'last_page' => $plans->lastPage(),
                'from' => $plans->firstItem(),
                'to' => $plans->lastItem(),
            ],
            'filters' => collect($filters)->only(['status', 'promotional', 'min_price', 'max_price', 'search', 'sort_by', 'sort_order'])->toArray()
        ];
    }

    /**
     * Buscar plano por UUID
     */
    public function findPlanByUuid(string $uuid): Plan
    {
        $plan = Plan::where('uuid', $uuid)->first();
        
        if (!$plan) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Plano não encontrado');
        }
        
        return $plan;
    }

    /**
     * Criar novo plano
     */
    public function createPlan(array $data): Plan
    {
        $planData = [
            'uuid' => Str::uuid(),
            ...$data
        ];

        return Plan::create($planData);
    }

    /**
     * Atualizar plano
     */
    public function updatePlan(string $uuid, array $data): Plan
    {
        $plan = Plan::where('uuid', $uuid)->firstOrFail();
        $plan->update($data);
        return $plan->fresh();
    }

    /**
     * Deletar plano
     */
    public function deletePlan(string $uuid): bool
    {
        $plan = Plan::where('uuid', $uuid)->firstOrFail();
        return $plan->delete();
    }

    /**
     * Alternar status do plano
     */
    public function togglePlanStatus(string $uuid): array
    {
        $plan = Plan::where('uuid', $uuid)->firstOrFail();
        
        $newStatus = $plan->status === 'active' ? 'inactive' : 'active';
        $plan->update(['status' => $newStatus]);

        return [
            'plan' => $plan->fresh(),
            'new_status' => $newStatus,
            'status_text' => $newStatus === 'active' ? 'ativado' : 'desativado'
        ];
    }

    /**
     * Obter estatísticas dos planos
     */
    public function getPlanStatistics(): array
    {
        return [
            'total_plans' => Plan::count(),
            'active_plans' => Plan::where('status', 'active')->count(),
            'inactive_plans' => Plan::where('status', 'inactive')->count(),
            'promotional_plans' => Plan::where('is_promotional', true)->count(),
            'average_price' => Plan::avg('price'),
            'min_price' => Plan::min('price'),
            'max_price' => Plan::max('price'),
        ];
    }

    /**
     * Alternar status do plano
     */
    public function toggleStatus(string $uuid): Plan
    {
        $plan = Plan::where('uuid', $uuid)->first();
        
        if (!$plan) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Plano não encontrado');
        }

        $plan->status = $plan->status === 'active' ? 'inactive' : 'active';
        $plan->save();

        return $plan;
    }
}