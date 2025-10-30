<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Ticket;
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
 * Tickets should only have the 'number' field and no associations (raffle_id, user_id, ticket_level, status).
 *
 * Use PopulateTicketsSeed to create the global ticket pool once.
 * Use RaffleTicketService to apply tickets from the pool to specific raffles.
 *
 * This job should be REMOVED or REFACTORED to work with the new architecture.
 */
class CreateTicketsBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutos timeout

    public $tries = 3;

    private int $raffleId;

    private int $startNumber;

    private int $endNumber;

    /**
     * Create a new job instance.
     */
    public function __construct(int $raffleId, int $startNumber, int $endNumber)
    {
        $this->raffleId = $raffleId;
        $this->startNumber = $startNumber;
        $this->endNumber = $endNumber;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $batchSize = $this->endNumber - $this->startNumber + 1;
        Log::info("🎫 Creating batch for raffle {$this->raffleId}: tickets {$this->startNumber}-{$this->endNumber} ({$batchSize} tickets)");

        $ticketsData = [];
        $now = now();

        for ($i = $this->startNumber; $i <= $this->endNumber; $i++) {

            $ticketsData[] = [
                'raffle_id' => $this->raffleId,
                'number' => str_pad((string) $i, 6, '0', STR_PAD_LEFT), // Formato 000001, 000002, etc.
                'status' => 'available',
                'user_id' => null,
                'ticket_level' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Insert em batch
        Ticket::insert($ticketsData);

        Log::info("✅ Batch completed for raffle {$this->raffleId}: tickets {$this->startNumber}-{$this->endNumber}");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("❌ Failed to create ticket batch for raffle {$this->raffleId} (tickets {$this->startNumber}-{$this->endNumber}): ".$exception->getMessage());
    }
}
