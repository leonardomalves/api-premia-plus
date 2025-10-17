<?php

namespace App\Jobs;

use App\Models\Raffle;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateTicketsForRafflesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutos timeout
    public $tries = 3;
    public $maxExceptions = 3;

    private int $raffleId;
    private int $totalTickets;

    /**
     * Create a new job instance.
     */
    public function __construct(int $raffleId, int $totalTickets = 100000)
    {
        $this->raffleId = $raffleId;
        $this->totalTickets = $totalTickets;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("🎫 Starting ticket creation orchestration for raffle {$this->raffleId} ({$this->totalTickets} tickets)");
        
        // Verificar se a rifa existe
        $raffle = Raffle::find($this->raffleId);
        if (!$raffle) {
            Log::error("❌ Raffle {$this->raffleId} not found");
            throw new \Exception("Raffle {$this->raffleId} not found");
        }

        // Verificar se já existem tickets para esta rifa
        $existingTickets = Ticket::where('raffle_id', $this->raffleId)->count();
        if ($existingTickets > 0) {
            Log::warning("⚠️ Raffle {$this->raffleId} already has {$existingTickets} tickets. Skipping creation.");
            return;
        }

        $batchSize = 1000; // Lotes de 100 tickets
        $totalBatches = ceil($this->totalTickets / $batchSize);
        
        Log::info("📦 Dispatching {$totalBatches} batch jobs of {$batchSize} tickets each");

        // Criar jobs auxiliares para cada lote
        for ($batch = 0; $batch < $totalBatches; $batch++) {
            $startNumber = ($batch * $batchSize) + 1;
            $endNumber = min(($batch + 1) * $batchSize, $this->totalTickets);
            
            // Dispatch do job auxiliar com delay para não sobrecarregar
            CreateTicketsBatchJob::dispatch($this->raffleId, $startNumber, $endNumber)
                ->delay(now()->addSeconds($batch * 2)); // 2 segundos entre cada batch
        }

        Log::info("🎉 Successfully dispatched all {$totalBatches} batch jobs for raffle {$this->raffleId}");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("❌ Failed to orchestrate ticket creation for raffle {$this->raffleId}: " . $exception->getMessage());
    }
}
