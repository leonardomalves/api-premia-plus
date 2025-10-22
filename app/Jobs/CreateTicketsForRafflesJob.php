<?php

namespace App\Jobs;

use App\Models\Raffle;
use App\Models\Ticket;
use App\Models\User;
use App\Services\BusinessRules\UserApplyToRaffleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * @deprecated This job is OBSOLETE after ticket system refactoring.
 *
 * The tickets table now represents a global pool of 10M pre-created ticket numbers.
 * Tickets are no longer associated directly with raffles via raffle_id.
 *
 * Use PopulateTicketsSeed to create the global ticket pool once.
 * Use RaffleTicketService to apply tickets from the pool to specific raffles.
 *
 * This job should be REMOVED or REFACTORED to work with the new raffle_tickets intermediate table.
 */
class CreateTicketsForRafflesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    
    protected User $user;
    protected Raffle $raffle;
    protected int $ticketCount;

    protected UserApplyToRaffleService $userApplyToRaffleService;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, Raffle $raffle, int $ticketCount)
    {
        $this->user = $user;
        $this->raffle = $raffle;
        $this->ticketCount = $ticketCount;
        $this->userApplyToRaffleService = new UserApplyToRaffleService();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->userApplyToRaffleService->applyUserToRaffle($this->user, $this->raffle, $this->ticketCount);
    }


}
