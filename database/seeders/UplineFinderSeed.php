<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\BusinessRules\UpLinesService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UplineFinderSeed extends Seeder
{
    public function run(): void

    {
        $uplinesService = new UpLinesService();
        $uplinesService->run('d26b09dd-0c6b-4442-9c75-09868680c034');
    }
}
