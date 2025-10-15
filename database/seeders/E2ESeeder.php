<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class E2ESeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([            
            CreateAdminSeed::class,
            CreateUsersSeed::class,
            PlanSeed::class,
            AddToCartSeed::class,
        ]);
    }
}
