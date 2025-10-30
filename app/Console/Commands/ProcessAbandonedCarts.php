<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\AbandonedCartJob;
use Illuminate\Console\Command;

class ProcessAbandonedCarts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'carts:process-abandoned';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process abandoned carts and mark them as abandoned after 24 hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🛒 Processing abandoned carts...');

        // Dispatch the job
        AbandonedCartJob::dispatch();

        $this->info('✅ Abandoned cart job dispatched successfully!');

        return Command::SUCCESS;
    }
}
