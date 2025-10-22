<?php

namespace App\Jobs;

use App\Models\Raffle;
use App\Models\User;
use App\Services\BusinessRules\UserApplyToRaffleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to apply user to raffle asynchronously
 *
 * This job handles the process of purchasing raffle tickets
 * through the queue system for better performance and scalability.
 */
class UserApplyToRaffleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 5;

    /**
     * The maximum number of seconds the job should run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public Raffle $raffle,
        public int $ticketCount
    ) {
        // Define a fila específica para aplicações de rifas
        $this->onQueue('raffle-applications');
    }

    /**
     * Execute the job.
     */
    public function handle(UserApplyToRaffleService $service): void
    {
        Log::info('Processing raffle application job', [
            'user_id' => $this->user->id,
            'raffle_id' => $this->raffle->id,
            'ticket_count' => $this->ticketCount,
            'attempt' => $this->attempts(),
        ]);

        $result = $service->applyUserToRaffle(
            $this->user,
            $this->raffle,
            $this->ticketCount
        );

        if (!$result['success']) {
            Log::warning('Raffle application failed', [
                'user_id' => $this->user->id,
                'raffle_id' => $this->raffle->id,
                'message' => $result['message'],
                'attempt' => $this->attempts(),
            ]);

            // Se for erro de validação de negócio, não tentar novamente
            if (str_contains($result['message'], 'não está ativa') ||
                str_contains($result['message'], 'já aplicou') ||
                str_contains($result['message'], 'Saldo insuficiente')) {
                // Marcar como falha permanente
                $this->delete();
                return;
            }

            // Para outros erros (tickets insuficientes, etc), tentar novamente
            throw new \Exception($result['message']);
        }

        Log::info('Raffle application completed successfully', [
            'user_id' => $this->user->id,
            'raffle_id' => $this->raffle->id,
            'tickets_count' => $result['tickets_count'],
            'total_cost' => $result['total_cost'],
            'duration_ms' => $result['duration_ms'],
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Raffle application job failed permanently', [
            'user_id' => $this->user->id,
            'raffle_id' => $this->raffle->id,
            'ticket_count' => $this->ticketCount,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Aqui você pode enviar notificação para o usuário, admin, etc.
    }
}
