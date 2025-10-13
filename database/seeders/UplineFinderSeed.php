<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use App\Services\BusinessRules\UpLinesService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UplineFinderSeed extends Seeder
{
    public function run(): void

    {
        $order = Order::where('status', 'approved')
        ->with('user')
        ->first();

        foreach ($order as $order) {
            $uplinesService = new UpLinesService();
            $uplinesService->run($order->user);
        }
    }
}
