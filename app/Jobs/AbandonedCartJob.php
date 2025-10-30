<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Cart;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AbandonedCartJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('🛒 Starting abandoned cart processing...');

        // Buscar carrinhos ativos há mais de 48 horas (conforme docs/itens.txt)
        $abandonedCarts = Cart::where('status', 'active')
            ->where('created_at', '<', Carbon::now()->subHours(48))
            ->with(['user', 'plan'])
            ->get();

        if ($abandonedCarts->isEmpty()) {
            Log::info('ℹ️ No abandoned carts found');

            return;
        }

        Log::info("📊 Found {$abandonedCarts->count()} abandoned carts to process");

        $processedCount = 0;
        $errorCount = 0;

        foreach ($abandonedCarts as $cart) {
            try {
                $cart->update(['status' => 'abandoned']);

                Log::info("🚫 Cart {$cart->uuid} marked as abandoned", [
                    'cart_id' => $cart->id,
                    'user_email' => $cart->user->email ?? 'N/A',
                    'plan_name' => $cart->plan->name ?? 'N/A',
                    'created_at' => $cart->created_at->format('Y-m-d H:i:s'),
                    'hours_old' => $cart->created_at->diffInHours(now()),
                ]);

                $processedCount++;

                // Aqui você poderia enviar email de recuperação de carrinho
                // $this->sendCartRecoveryEmail($cart);

            } catch (\Exception $e) {
                $errorCount++;
                Log::error("❌ Error processing cart {$cart->uuid}: {$e->getMessage()}", [
                    'cart_id' => $cart->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info('✅ Abandoned cart processing completed', [
            'total_found' => $abandonedCarts->count(),
            'processed' => $processedCount,
            'errors' => $errorCount,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('❌ AbandonedCartJob failed: '.$exception->getMessage(), [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Enviar email de recuperação de carrinho (implementar conforme necessário)
     */
    private function sendCartRecoveryEmail(Cart $cart): void
    {
        // TODO: Implementar envio de email de recuperação
        // Exemplo: Mail::to($cart->user->email)->send(new CartRecoveryMail($cart));

        Log::info('📧 Cart recovery email should be sent', [
            'cart_id' => $cart->id,
            'user_email' => $cart->user->email ?? 'N/A',
        ]);
    }
}
