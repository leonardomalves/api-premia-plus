<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomerCartController extends Controller
{
    /**
     * Adicionar item ao carrinho (criar ou atualizar)
     * Regra: Usuário só pode ter 1 item não pago no carrinho
     */
    public function addToCart(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $planId = $request->input('plan_id');

            // Validar se o plano existe e está ativo
            $plan = Plan::where('id', $planId)
                ->where('status', 'active')
                ->first();

            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plano não encontrado ou inativo'
                ], 404);
            }

            // Verificar se já existe um carrinho ativo para este usuário
            $existingCart = Cart::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if ($existingCart) {
                // Atualizar o plan_id do carrinho existente
                $existingCart->update([
                    'plan_id' => $planId
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Carrinho atualizado com sucesso',
                    'data' => [
                        'cart' => $existingCart->fresh()->load('plan'),
                        'action' => 'updated'
                    ]
                ], 200);
            } else {
                // Criar novo carrinho
                $cart = Cart::create([
                    'uuid' => Str::uuid(),
                    'user_id' => $user->id,
                    'plan_id' => $planId,
                    'status' => 'active'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Item adicionado ao carrinho com sucesso',
                    'data' => [
                        'cart' => $cart->load('plan'),
                        'action' => 'created'
                    ]
                ], 201);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao adicionar item ao carrinho',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Visualizar carrinho atual
     */
    public function viewCart(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $cart = Cart::where('user_id', $user->id)
                ->where('status', 'active')
                ->with('plan')
                ->first();

            if (!$cart) {
                return response()->json([
                    'success' => true,
                    'message' => 'Carrinho vazio',
                    'data' => [
                        'cart' => null,
                        'total' => 0
                    ]
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Carrinho carregado com sucesso',
                'data' => [
                    'cart' => $cart,
                    'total' => 1
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar carrinho',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remover item do carrinho
     */
    public function removeFromCart(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $cart = Cart::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if (!$cart) {
                return response()->json([
                    'success' => false,
                    'message' => 'Carrinho vazio'
                ], 404);
            }

            $cart->update(['status' => 'abandoned']);

            return response()->json([
                'success' => true,
                'message' => 'Item removido do carrinho com sucesso'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover item do carrinho',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpar carrinho (marcar como abandonado)
     */
    public function clearCart(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $updatedCount = Cart::where('user_id', $user->id)
                ->where('status', 'active')
                ->update(['status' => 'abandoned']);

            return response()->json([
                'success' => true,
                'message' => 'Carrinho limpo com sucesso',
                'data' => [
                    'cleared_items' => $updatedCount
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao limpar carrinho',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Finalizar compra (checkout) - Criar Order a partir do Cart
     */
    public function checkout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Buscar carrinho ativo do usuário
            $cart = Cart::where('user_id', $user->id)
                ->where('status', 'active')
                ->with('plan')
                ->first();

            if (!$cart) {
                return response()->json([
                    'success' => false,
                    'message' => 'Carrinho vazio'
                ], 404);
            }

            // Criar Order a partir do Cart
            $order = Order::create([
                'uuid' => Str::uuid(),
                'user_id' => $user->id,
                'user_metadata' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
                'plan_id' => $cart->plan_id,
                'plan_metadata' => [
                    'name' => $cart->plan->name,
                    'description' => $cart->plan->description,
                    'price' => $cart->plan->price,
                    'grant_tickets' => $cart->plan->grant_tickets,
                    'commission_level_1' => $cart->plan->commission_level_1,
                    'commission_level_2' => $cart->plan->commission_level_2,
                    'commission_level_3' => $cart->plan->commission_level_3,
                    'is_promotional' => $cart->plan->is_promotional,
                ],
                'status' => 'pending'
            ]);

            // Atualizar Cart com order_id e status completed
            $cart->update([
                'order_id' => $order->id,
                'status' => 'completed'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Compra finalizada com sucesso',
                'data' => [
                    'order' => $order->load('plan'),
                    'cart' => $cart->fresh()
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao finalizar compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
