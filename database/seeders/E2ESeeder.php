<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class E2ESeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando simulação E2E completa...');

                Artisan::call('migrate:fresh'); // Limpa o banco antes de rodar a simulação

                $this->call([
                    AdminDirectSeed::class,     // 1. Criar admins DIRETAMENTE no banco
                    PlanSeed::class,            // 2. Criar planos ANTES dos usuários
                    CreateUsersSeed::class,     // 3. Criar usuários via API
                    AddToCartSeed::class,       // 4. Simular adição ao carrinho
                    ProcessCartStatusSeed::class,   // 5. Processar carrinhos → orders
                    ProcessOrderStatusSeed::class,  // 6. Aprovar/rejeitar orders
                ]);

        $this->command->info('✅ Simulação E2E completa finalizada!');
    }
}
