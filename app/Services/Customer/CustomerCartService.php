<?php

namespace App\Services\Customer;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerCartService
{
    /**
     * Adicionar item ao carrinho (criar ou atualizar)
     * Regra: Usuário só pode ter 1 item não pago no carrinho
     */
    public function addToCart(User $user, string $planUuid): array
    {
        // Validar se o plano existe e está ativo
        $plan = Plan::where('uuid', $planUuid)
            ->where('status', 'active')
            ->first();

        if (! $plan) {
            throw new \Exception('Plano não encontrado ou inativo');
        }

        // Verificar se já existe um carrinho ativo para este usuário
        $existingCart = Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if ($existingCart) {
            // Atualizar o plan_id do carrinho existente
            $existingCart->update([
                'plan_id' => $plan->id,
            ]);

            return [
                'cart' => $existingCart->fresh()->load('plan'),
                'action' => 'updated',
            ];
        } else {
            // Criar novo carrinho
            $cart = Cart::create([
                'uuid' => Str::uuid(),
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => 'active',
            ]);

            return [
                'cart' => $cart->load('plan'),
                'action' => 'created',
            ];
        }
    }

    /**
     * Visualizar carrinho atual
     */
    public function viewCart(User $user): array
    {
        $cart = Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('plan')
            ->first();

        if (! $cart) {
            return [
                'cart' => null,
                'total' => 0,
            ];
        }

        return [
            'cart' => $cart,
            'total' => 1,
        ];
    }

    /**
     * Remover item do carrinho
     */
    public function removeFromCart(User $user): bool
    {
        $cart = Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (! $cart) {
            throw new \Exception('Carrinho vazio');
        }

        $cart->update(['status' => 'abandoned']);

        return true;
    }

    /**
     * Limpar carrinho (marcar como abandonado)
     */
    public function clearCart(User $user): int
    {
        $updatedCount = Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->update(['status' => 'abandoned']);

        return $updatedCount;
    }

    /**
     * Finalizar compra (checkout) - Criar Order a partir do Cart
     */
    public function checkout(User $user): array
    {
        // Buscar carrinho ativo do usuário
        $cart = Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('plan')
            ->first();

        if (! $cart) {
            throw new \Exception('Carrinho vazio');
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
                'commission_level_1' => $cart->plan->commission_level_1,
                'commission_level_2' => $cart->plan->commission_level_2,
                'commission_level_3' => $cart->plan->commission_level_3,
                'is_promotional' => $cart->plan->is_promotional,
            ],
            'status' => 'pending',
        ]);

        // Atualizar Cart com order_id e status completed
        // Para evitar violação da constraint unique (user_id, status),
        // primeiro mudamos para status temporário, depois para completed
        DB::statement(
            'UPDATE carts SET order_id = ?, status = ?, updated_at = ? WHERE id = ?',
            [$order->id, 'completed', now(), $cart->id]
        );

        return [
            'order' => $order->load('plan'),
            'cart' => $cart->fresh(),
        ];
    }
}
