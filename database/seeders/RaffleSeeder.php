<?php

namespace Database\Seeders;

use App\Models\Raffle;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RaffleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('� Iniciando seed de raffles...');

        // Busca um admin para ser o criador dos raffles
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $this->command->error('❌ Nenhum admin encontrado! Execute CreateAdminSeed primeiro.');
            return;
        }

        $raffleTemplates = [
            [
                'title' => 'iPhone 15 Pro Max 512GB',
                'description' => 'Último modelo do iPhone com 512GB de armazenamento, cor Titânio Natural. Produto lacrado com garantia Apple.',
                'prize_value' => 8999.99,
                'operation_cost' => 500.00,
                'unit_ticket_value' => 25.00,
                'tickets_required' => 5,
                'min_ticket_level' => 0,
                'max_tickets_per_user' => 10,
                'status' => 'active',
                'notes' => 'Campanha de lançamento - produto premium',
                'liquid_value' => 0,
                'liquidity_ratio' => 0
            ],
            [
                'title' => 'MacBook Pro M3 16" 1TB',
                'description' => 'MacBook Pro com chip M3, tela de 16 polegadas, 1TB SSD, 32GB RAM. Ideal para profissionais.',
                'prize_value' => 15999.99,
                'operation_cost' => 800.00,
                'unit_ticket_value' => 50.00,
                'tickets_required' => 5,
                'min_ticket_level' => 0,
                'max_tickets_per_user' => 5,
                'status' => 'active',
                'notes' => 'Produto para profissionais de tecnologia',
                'liquid_value' => 0,
                'liquidity_ratio' => 0
            ],
            [
                'title' => 'PlayStation 5 + 2 Controles',
                'description' => 'Console PlayStation 5 lacrado com 2 controles DualSense e jogo Spider-Man 2 incluso.',
                'prize_value' => 3499.99,
                'operation_cost' => 200.00,
                'unit_ticket_value' => 15.00,
                'tickets_required' => 5,
                'min_ticket_level' => 0,
                'max_tickets_per_user' => 20,
                'status' => 'active',
                'notes' => 'Campanha focada em gamers',
                'liquid_value' => 0,
                'liquidity_ratio' => 0
            ],
            [
                'title' => 'R$ 10.000 em Dinheiro',
                'description' => 'Prêmio em dinheiro vivo de R$ 10.000,00 para usar como quiser. Valor depositado via PIX.',
                'prize_value' => 10000.00,
                'operation_cost' => 300.00,
                'unit_ticket_value' => 20.00,
                'tickets_required' => 5,
                'min_ticket_level' => 0,
                'max_tickets_per_user' => 25,
                'status' => 'active',
                'notes' => 'Prêmio em dinheiro sempre atrai',
                'liquid_value' => 0,
                'liquidity_ratio' => 0
            ],
            [
                'title' => 'Apple Watch Ultra 2',
                'description' => 'Apple Watch Ultra 2 com pulseira Ocean Band, GPS + Cellular, resistente e ideal para esportes.',
                'prize_value' => 4999.99,
                'operation_cost' => 250.00,
                'unit_ticket_value' => 12.00,
                'tickets_required' => 5,
                'min_ticket_level' => 0,
                'max_tickets_per_user' => 15,
                'status' => 'active',
                'notes' => 'Produto em preparação',
                'liquid_value' => 0,
                'liquidity_ratio' => 0
            ],
            [
                'title' => 'Samsung Galaxy S24 Ultra 512GB',
                'description' => 'Smartphone Samsung Galaxy S24 Ultra com 512GB, S Pen incluída, câmera de 200MP.',
                'prize_value' => 6499.99,
                'operation_cost' => 350.00,
                'unit_ticket_value' => 18.00,
                'tickets_required' => 5,
                'min_ticket_level' => 0,
                'max_tickets_per_user' => 12,
                'status' => 'active',
                'notes' => 'Campanha pausada temporariamente',
                'liquid_value' => 0,
                'liquidity_ratio' => 0
            ]
        ];

        $this->command->info("🔢 Criando " . count($raffleTemplates) . " raffles base...");

        foreach ($raffleTemplates as $index => $template) {
            $template['created_by'] = $admin->id;
            
            Raffle::create($template);
            
            $this->command->info("✅ Raffle criado: {$template['title']}");
        }

        // Criar raffles adicionais aleatórios
        $this->command->info("🎰 Criando raffles adicionais aleatórios...");
        
        $prizes = [
            'Nintendo Switch OLED', 'Xbox Series X', 'iPad Pro M2', 'AirPods Pro 2',
            'Smart TV 65" 4K', 'Notebook Gamer', 'Câmera Canon EOS', 'Drone DJI Mini',
            'R$ 5.000 em Dinheiro', 'Perfume Importado Kit', 'Relógio Smartwatch',
            'Fone Beats Studio', 'Tablet Samsung', 'Kindle Oasis'
        ];

        for ($i = 0; $i < 10; $i++) {
            $prize = $prizes[array_rand($prizes)];
            $prizeValue = rand(500, 5000);
            $operationCost = $prizeValue * 0.1; // 10% do valor do prêmio
            $ticketValue = rand(5, 30);
            $ticketsNeeded = intval($prizeValue / $ticketValue);
            
            Raffle::create([
                'title' => $prize . ' #' . ($i + 1),
                'description' => "Sorteio de {$prize} em excelente estado. Produto original com garantia.",
                'prize_value' => $prizeValue,
                'operation_cost' => $operationCost,
                'unit_ticket_value' => $ticketValue,
                'tickets_required' => $ticketsNeeded,
                'min_ticket_level' => 0,
                'max_tickets_per_user' => rand(5, 20),
                'status' => ['active', 'active', 'inactive'][rand(0, 2)],
                'created_by' => $admin->id,
                'notes' => "Raffle gerado automaticamente - {$prize}",
                'liquid_value' => 0,
                'liquidity_ratio' => 0
            ]);
        }

        $totalRaffles = count($raffleTemplates) + 10;
        $this->command->info("✅ Seed de raffles concluído! Total: {$totalRaffles} raffles criados.");
        $this->command->info("📊 Status: " . Raffle::where('status', 'active')->count() . " ativos, " . 
                            Raffle::where('status', 'draft')->count() . " rascunhos, " . 
                            Raffle::where('status', 'inactive')->count() . " inativos");
    }
}
