<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Criar admin primeiro
            CreateAdminSeed::class,

            // Depois criar usuários de teste
            CreateUsersSeed::class,

            // Seeds de dados complementares
            PlanSeed::class,

            // Simular comportamento real dos usuários
            AddToCartSeed::class,

            // Processar carrinhos e criar orders
            ProcessCartStatusSeed::class,

            // Processar status das orders (aprovar/rejeitar/cancelar)
            ProcessOrderStatusSeed::class,
        ]);
    }
}
