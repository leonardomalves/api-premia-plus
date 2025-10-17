<?php

namespace Database\Seeders;

use App\Models\Raffle;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserApplyToRaffleSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::find(9);
        $raffles = Raffle::all();
        
        foreach ($raffles as $raffle) {
            $minTicketLevel = $raffle->min_ticket_level;
            $ticketsRequired = $raffle->tickets_required;
            
            // Buscar tickets do usuário que atendem o nível mínimo da rifa
            $userTickets = $user->walletTickets()
                ->where('ticket_level', '>=', $minTicketLevel)
                ->where('status', 'active')
                ->orderBy('ticket_level', 'asc') // Usar tickets de menor nível primeiro
                ->get();

            // Calcular tickets disponíveis
            $availableTickets = $userTickets->sum(function($ticket) {
                return ($ticket->total_tickets - $ticket->total_tickets_used) + $ticket->bonus_tickets;
            });

            echo "\n========================================\n";
            echo "Rifa: {$raffle->title}\n";
            echo "Nível mínimo: {$minTicketLevel}\n";
            echo "Tickets requeridos: {$ticketsRequired}\n";
            echo "Tickets disponíveis do usuário: {$availableTickets}\n";
            
            // Verificar se o usuário tem tickets suficientes
            if ($availableTickets < $ticketsRequired) {
                echo "❌ INSUFICIENTE - Usuário não tem tickets suficientes!\n";
                continue;
            }
            
            // Aplicar os tickets na rifa
            $remainingToApply = $ticketsRequired;
            $totalApplied = 0;
            
            foreach ($userTickets as $walletTicket) {
                if ($remainingToApply <= 0) {
                    break;
                }
                
                // Verificar quantos tickets disponíveis neste wallet
                $available = ($walletTicket->total_tickets - $walletTicket->total_tickets_used) 
                           + $walletTicket->bonus_tickets;
                
                if ($available > 0) {
                    // Decrementar a quantidade necessária (ou o disponível, o que for menor)
                    $toDecrement = min($remainingToApply, $available);
                    $decremented = $walletTicket->decrementIn($toDecrement);
                    
                    $totalApplied += $decremented;
                    $remainingToApply -= $decremented;
                    
                    echo "  -> Decrementado {$decremented} tickets do wallet #{$walletTicket->id} (Nível: {$walletTicket->ticket_level})\n";
                }
            }
            
            if ($totalApplied === $ticketsRequired) {
                echo "✅ SUCESSO - {$totalApplied} tickets aplicados na rifa!\n";
            } else {
                echo "⚠️ PARCIAL - Apenas {$totalApplied} de {$ticketsRequired} tickets foram aplicados\n";
            }
        }
        
        echo "\n========================================\n";
        echo "Processo concluído!\n";
    }
}
