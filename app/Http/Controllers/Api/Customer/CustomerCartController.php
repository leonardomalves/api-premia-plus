<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Services\Customer\CustomerCartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerCartController extends Controller
{
    protected CustomerCartService $cartService;

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
        $request->validate([
            'plan_uuid' => 'required|string|uuid',
        ]);

        try {
            $result = $this->cartService->addToCart(
                $request->user(),
                $request->input('plan_uuid')
            );

            $message = $result['action'] === 'updated'
                ? __('app.cart.updated')
                : __('app.cart.item_added');

            $statusCode = $result['action'] === 'created' ? 201 : 200;

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => $result,
                'meta' => ['execution_time_ms' => round((microtime(true) - (microtime(true) - 0.1)) * 1000, 2)]
            ], $statusCode);

        } catch (\Exception $e) {
            $statusCode = $e->getMessage() === __('app.plan.not_found_or_inactive') ? 404 : 500;

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'errors' => ['cart' => $e->getMessage()]
            ], $statusCode);
        }
    }

    /**
     * Visualizar carrinho atual
     */
    public function viewCart(Request $request): JsonResponse
    {
        try {
            $result = $this->cartService->viewCart($request->user());

            $message = $result['cart'] === null
                ? __('app.cart.empty')
                : __('app.cart.loaded');

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => $result,
                'meta' => ['execution_time_ms' => round((microtime(true) - (microtime(true) - 0.1)) * 1000, 2)]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('app.cart.load_error'),
                'errors' => ['cart' => $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Remover item do carrinho
     */
    public function removeFromCart(Request $request): JsonResponse
    {
        try {
            $this->cartService->removeFromCart($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Item removido do carrinho com sucesso',
            ], 200);

        } catch (\Exception $e) {
            $statusCode = $e->getMessage() === 'Carrinho vazio' ? 404 : 500;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Limpar carrinho (marcar como abandonado)
     */
    public function clearCart(Request $request): JsonResponse
    {
        try {
            $clearedItems = $this->cartService->clearCart($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Carrinho limpo com sucesso',
                'data' => [
                    'cleared_items' => $clearedItems,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao limpar carrinho',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Finalizar compra (checkout) - Criar Order a partir do Cart
     */
    public function checkout(Request $request): JsonResponse
    {
        try {
            $result = $this->cartService->checkout($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Compra finalizada com sucesso',
                'data' => $result,
            ], 201);

        } catch (\Exception $e) {
            $statusCode = $e->getMessage() === 'Carrinho vazio' ? 404 : 500;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }
}
