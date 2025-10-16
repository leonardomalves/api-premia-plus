<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Services\Customer\CustomerCartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

    
class CustomerCartController extends Controller
{

    protected $cartService;

    public function __construct(CustomerCartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Adicionar item ao carrinho (criar ou atualizar)
     * Regra: Usuário só pode ter 1 item não pago no carrinho
     */
    
    public function addToCart(Request $request): JsonResponse
    {

        return $this->cartService->addToCart($request);
    }

    /**
     * Visualizar carrinho atual
     */
    public function viewCart(Request $request): JsonResponse
    {
       return $this->cartService->viewCart($request);
    }

    /**
     * Remover item do carrinho
     */
    public function removeFromCart(Request $request): JsonResponse
    {
        return $this->cartService->removeFromCart($request);
    }

    /**
     * Limpar carrinho (marcar como abandonado)
     */
    public function clearCart(Request $request): JsonResponse
    {
        return $this->cartService->clearCart($request);
    }

    /**
     * Finalizar compra (checkout) - Criar Order a partir do Cart
     */
    public function checkout(Request $request): JsonResponse
    {
        return $this->cartService->checkout($request);
    }
}
