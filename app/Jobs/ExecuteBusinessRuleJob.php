<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\BusinessRules\ExecuteBusinessRule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExecuteBusinessRuleJob implements ShouldQueue
{
    use Queueable;

    private $order;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $executeBusinessRule = new ExecuteBusinessRule();
        $executeBusinessRule->execute($this->order);
    }
}
