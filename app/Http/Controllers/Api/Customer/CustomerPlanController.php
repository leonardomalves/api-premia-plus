<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\Customer\CustomerPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerPlanController extends Controller
{

    protected $customerPlanService;
    public function __construct(CustomerPlanService $customerPlanService)
    {
        $this->customerPlanService = $customerPlanService;
    }
    /**
     * Listar todos os planos ativos
     */
    public function index(Request $request): JsonResponse
    {
        return $this->customerPlanService->index($request);
    }

    /**
     * Mostrar detalhes de um plano específico
     */
    public function show(string $uuid): JsonResponse
    {
      return $this->customerPlanService->show($uuid);
    }

    /**
     * Listar apenas planos promocionais
     */
    public function promotional(): JsonResponse
    {
        return $this->customerPlanService->promotional();
    }

    /**
     * Buscar planos por faixa de preço
     */
    public function search(Request $request): JsonResponse
    {
        return $this->customerPlanService->search($request);
    }
}
