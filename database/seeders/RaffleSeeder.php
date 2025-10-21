<?php

namespace Database\Seeders;

use App\Models\Raffle;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RaffleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🎰 Iniciando seed de raffles...');

        // Busca um admin para ser o criador dos raffles
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $this->command->error('❌ Nenhum admin encontrado! Execute AdminDirectSeed primeiro.');
            return;
        }

        $this->command->info("👤 Admin criador: {$admin->name} (ID: {$admin->id})");

        // Definir quantidade de raffles a criar
        $quantity = 10000;
        $batchSize = 500; // Processar em lotes para melhor performance
        
        $this->command->info("📦 Criando {$quantity} raffles em lotes de {$batchSize}...");

        $prizes = [
            'Nintendo Switch OLED', 'Xbox Series X', 'iPad Pro M2', 'AirPods Pro 2',
            'Smart TV 65" 4K', 'Notebook Gamer', 'Câmera Canon EOS', 'Drone DJI Mini',
            'R$ 5.000 em Dinheiro', 'Perfume Importado Kit', 'Relógio Smartwatch',
            'Fone Beats Studio', 'Tablet Samsung', 'Kindle Oasis', 'iPhone 15',
            'MacBook Air', 'PlayStation 5', 'Bicicleta Elétrica', 'Ar Condicionado',
        ];

        $statusOptions = ['active', 'active', 'active', 'inactive']; // 75% ativos, 25% inativos

        $created = 0;
        $startTime = microtime(true);

        // Criar em lotes para melhor performance
        for ($batch = 0; $batch < ceil($quantity / $batchSize); $batch++) {
            $rafflesData = [];
            $currentBatchSize = min($batchSize, $quantity - $created);

            for ($i = 0; $i < $currentBatchSize; $i++) {
                $prize = $prizes[array_rand($prizes)];
                $prizeValue = rand(500, 5000) + (rand(0, 99) / 100);
                $operationCost = round($prizeValue * 0.1, 2); // 10% do valor do prêmio
                $ticketValue = rand(1, 3) + (rand(0, 99) / 100); // Entre 1.00 e 3.99
                $ticketsNeeded = rand(100, 500);
                $liquidityRatio = rand(12, 15);

                $totalCost = $prizeValue + $operationCost;
                $liquidValue = round(($totalCost * ($liquidityRatio / 100)) + $totalCost, 2);

                $rafflesData[] = [
                    'uuid' => \Illuminate\Support\Str::uuid(),
                    'title' => $prize . ' #' . ($created + $i + 1),
                    'description' => "Sorteio de {$prize} em excelente estado. Produto original com garantia de 1 ano.",
                    'prize_value' => $prizeValue,
                    'operation_cost' => $operationCost,
                    'unit_ticket_value' => $ticketValue,
                    'min_tickets_required' => $ticketsNeeded,
                    'status' => $statusOptions[array_rand($statusOptions)],
                    'created_by' => $admin->id,
                    'notes' => "Raffle gerado automaticamente - {$prize}",
                    'liquid_value' => $liquidValue,
                    'liquidity_ratio' => $liquidityRatio,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insert em lote para melhor performance
            DB::table('raffles')->insert($rafflesData);
            
            $created += $currentBatchSize;
            $progress = round(($created / $quantity) * 100, 1);
            
            $this->command->info("  ✅ Lote " . ($batch + 1) . " criado: {$currentBatchSize} raffles | Progresso: {$progress}% ({$created}/{$quantity})");
        }

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        // Estatísticas finais
        $this->command->info('');
        $this->command->info('📊 ESTATÍSTICAS FINAIS');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info("✅ Total de raffles criados: {$created}");
        $this->command->info("⏱️  Tempo de execução: {$duration}s");
        $this->command->info("⚡ Média: " . round($created / $duration, 0) . " raffles/segundo");
        
        $activeCount = Raffle::where('status', 'active')->count();
        $inactiveCount = Raffle::where('status', 'inactive')->count();
        $totalValue = Raffle::sum('prize_value');
        $avgValue = round(Raffle::avg('prize_value'), 2);
        
        $this->command->info("🟢 Ativos: {$activeCount}");
        $this->command->info("🔴 Inativos: {$inactiveCount}");
        $this->command->info("💰 Valor total em prêmios: R$ " . number_format($totalValue, 2, ',', '.'));
        $this->command->info("📈 Valor médio: R$ " . number_format($avgValue, 2, ',', '.'));
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('✅ Seed de raffles concluído com sucesso!');
    }
}
