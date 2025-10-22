<?php

namespace Database\Seeders;

use App\Models\Ticket;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        
        $this->command->info('🚀 Iniciando simulação E2E completa...');

        Artisan::call('migrate:fresh'); // Limpa o banco antes de rodar a simulação

        $this->call([
            AdminDirectSeed::class,         // 1. Criar admins DIRETAMENTE no banco
            PopulateTicketsSeed::class,     // 2. Popular tickets diretamente
            PlanSeed::class,                // 3. Criar planos ANTES dos usuários
            CreateUsersSeed::class,         // 4. Criar usuários via API
            AddToCartSeed::class,           // 5. Simular adição ao carrinho
            ProcessCartStatusSeed::class,   // 6. Processar carrinhos → orders
            ProcessOrderStatusSeed::class,  // 7. Aprovar/rejeitar orders
            WalletSeed::class,              // 8. Creditar saldo nas wallets
            RaffleSeeder::class,            // 9. Criar rifas
            UserApplyToRaffleSeed::class,   // 10. Usuários aplicam em rifas
        ]);

        Ticket::factory()->count(10)->create();

        $this->command->info('✅ Simulação E2E completa finalizada!');
    }
}
