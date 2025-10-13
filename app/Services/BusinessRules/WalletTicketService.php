<?php

namespace App\Services\BusinessRules;

use App\Models\Order;
use App\Models\WalletTicket;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WalletTicketService
{
    /**
     * Cria wallet tickets para um pedido aprovado
     */
    public function createWalletTicket(Order $order): ?WalletTicket
    {
        try {
            // Valida se o pedido está aprovado
            if ($order->status !== 'approved') {
                Log::warning("Tentativa de criar wallet ticket para pedido não aprovado: {$order->id}");
                return null;
            }

            // Valida se o plano existe e tem os dados necessários
            if (!$order->plan) {
                Log::warning("Pedido {$order->id} não possui plano associado.");
                return null;
            }

            $grantTickets = $order->plan_metadata['grant_tickets'] ?? $order->plan->grant_tickets ?? 0;
            if (!$grantTickets || $grantTickets <= 0) {
                Log::warning("Plano {$order->plan->name} não possui tickets para conceder.");
                return null;
            }

            // Calcula data de expiração (30 dias a partir de agora)
            $expirationDate = now()->addDays(30);

            $walletTicket = WalletTicket::updateOrCreate([
                'order_id' => $order->id,
            ], [
                'uuid' => Str::uuid(),
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'plan_id' => $order->plan_id,
                'ticket_level' => $order->plan_metadata['ticket_level'] ?? $order->plan->ticket_level ?? 1,
                'total_tickets' => $grantTickets,
                'expiration_date' => $expirationDate,
                'status' => 'active',
            ]);

            Log::info("Wallet ticket criado/atualizado para pedido {$order->id}: {$walletTicket->uuid}");
            return $walletTicket;

        } catch (\Exception $e) {
            Log::error("Erro ao criar wallet ticket para pedido {$order->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Processa todos os pedidos aprovados e cria wallet tickets
     */
    public function processApprovedOrders(): array
    {
        $approvedOrders = Order::where('status', 'approved')
            ->with(['plan', 'user'])
            ->get();

        if ($approvedOrders->isEmpty()) {
            Log::info('Nenhum pedido aprovado encontrado para criar wallet tickets.');
            return ['created' => 0, 'updated' => 0, 'errors' => 0];
        }

        $created = 0;
        $updated = 0;
        $errors = 0;

        foreach ($approvedOrders as $order) {
            $walletTicket = $this->createWalletTicket($order);
            
            if ($walletTicket) {
                if ($walletTicket->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            } else {
                $errors++;
            }
        }

        Log::info("Wallet tickets processados: {$created} criados, {$updated} atualizados, {$errors} erros.");
        return ['created' => $created, 'updated' => $updated, 'errors' => $errors];
    }
}