<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\BusinessRules\ExecuteBusinessRule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ExecuteBusinessRuleJob implements ShouldQueue
{
    use Queueable;

    public int $orderId;
    public int $tries = 3;
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Execute the job.
     */
    public function handle(ExecuteBusinessRule $executeBusinessRule): void
    {
        Log::info("Executing business rules for Order ID: {$this->orderId}");

        // Find order at execution time
        $order = Order::find($this->orderId);
        
        if (!$order) {
            Log::error("Order not found: {$this->orderId}");
            return;
        }

        try {
            $result = $executeBusinessRule->execute($order);
            Log::info("Business rules executed successfully", $result);
        } catch (\Exception $e) {
            Log::error("Error executing business rules for Order {$this->orderId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ExecuteBusinessRuleJob failed for Order {$this->orderId}: " . $exception->getMessage());
    }
}
