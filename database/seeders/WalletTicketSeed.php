<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\WalletTicket;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WalletTicketSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $approvedOrders = Order::where('status', 'approved')
            ->with(['plan', 'user'])
            ->get();

        if ($approvedOrders->isEmpty()) {
            $this->command->info('Nenhum pedido aprovado encontrado para criar wallet tickets.');
            return;
        }

        $created = 0;
        $updated = 0;

        foreach ($approvedOrders as $order) {
            try {
                // Valida se o plano existe e tem os dados necessários
                if (!$order->plan) {
                    $this->command->warn("Pedido {$order->id} não possui plano associado. Pulando...");
                    continue;
                }

                if (!$order->plan->grant_tickets || $order->plan->grant_tickets <= 0) {
                    $this->command->warn("Plano {$order->plan->name} não possui tickets para conceder. Pulando...");
                    continue;
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
                    'ticket_level' => $order->plan_metadata['ticket_level'] ?? 1,
                    'total_tickets' => $order->plan_metadata['grant_tickets'],
                    'expiration_date' => $expirationDate,
                    'status' => 'active',
                ]);

                if ($walletTicket->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }

            } catch (\Exception $e) {
                $this->command->error("Erro ao criar wallet ticket para pedido {$order->id}: " . $e->getMessage());
            }
        }

        $this->command->info("Wallet tickets processados: {$created} criados, {$updated} atualizados.");
    }
}
