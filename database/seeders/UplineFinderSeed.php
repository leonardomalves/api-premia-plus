<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UplineFinderSeed extends Seeder
{
    /**
     * Profundidade configurável para buscar uplines
     */
    private $maxLevels = 3;
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔍 Iniciando busca de uplines...');
        
        // Buscar todos os usuários
        $users = User::where('uuid', '1868fb8c-d68a-469d-8eaf-1022a02657c7')->get();
        
        if ($users->isEmpty()) {
            $this->command->warn('⚠️ Nenhum usuário encontrado. Execute primeiro o CreateUserSeed.');
            return;
        }
        
        $this->command->info("👥 Processando {$users->count()} usuários...");
        
        foreach ($users as $user) {
            $this->findUplines($user);
        }
        
        $this->command->info('✅ Busca de uplines concluída!');
    }
    
    /**
     * Busca uplines para um usuário específico
     */
    private function findUplines(User $user): void
    {
        $this->command->info("👤 Usuário: {$user->name} (ID: {$user->id})");
        
        if (!$user->sponsor_id) {
            $this->command->warn("   ⚠️ Sem patrocinador");
            return;
        }
        
        // Buscar uplines com profundidade configurável
        $uplines = $this->getUplines($user, $this->maxLevels);
        
        if (empty($uplines)) {
            $this->command->warn("   ⚠️ Nenhum upline encontrado");
            return;
        }
        
        $this->command->info("   📊 Uplines encontrados: " . count($uplines));
        
        // Exibir cada upline
        foreach ($uplines as $level => $upline) {
            $this->command->info("   ↳ Nível " . ($level + 1) . ": {$upline->name} (ID: {$upline->id})");
        }
    }
    
    /**
     * Busca uplines com profundidade configurável
     */
    private function getUplines(User $user, int $maxLevels): array
    {
        $uplines = [];
        $currentUser = $user;
        $level = 0;
        
        while ($level < $maxLevels && $currentUser->sponsor_id) {
            $sponsor = User::find($currentUser->sponsor_id);
            
            if (!$sponsor) {
                break;
            }
            
            $uplines[$level] = $sponsor;
            $currentUser = $sponsor;
            $level++;
        }
        
        return $uplines;
    }
    
    /**
     * Configura a profundidade máxima de uplines
     */
    public function setMaxLevels(int $levels): self
    {
        $this->maxLevels = $levels;
        return $this;
    }
    
    /**
     * Exibe estatísticas dos uplines
     */
    public function showStatistics(): void
    {
        $this->command->info('📈 Estatísticas de Uplines:');
        
        $totalUsers = User::count();
        $usersWithSponsors = User::whereNotNull('sponsor_id')->count();
        
        $this->command->info("   - Total de usuários: {$totalUsers}");
        $this->command->info("   - Usuários com patrocinador: {$usersWithSponsors}");
        $this->command->info("   - Profundidade configurada: {$this->maxLevels} níveis");
        
        // Contar usuários por nível de upline
        $this->countUplineLevels();
    }
    
    /**
     * Conta usuários por nível de upline
     */
    private function countUplineLevels(): void
    {
        $this->command->info('📊 Distribuição por níveis:');
        
        $users = User::whereNotNull('sponsor_id')->get();
        $levelCounts = [];
        
        foreach ($users as $user) {
            $uplines = $this->getUplines($user, $this->maxLevels);
            $levelCount = count($uplines);
            
            if (!isset($levelCounts[$levelCount])) {
                $levelCounts[$levelCount] = 0;
            }
            $levelCounts[$levelCount]++;
        }
        
        for ($i = 1; $i <= $this->maxLevels; $i++) {
            $count = $levelCounts[$i] ?? 0;
            $this->command->info("   - {$i} nível(is): {$count} usuários");
        }
    }
}
