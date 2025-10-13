<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SimpleUplineSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔍 Buscando uplines...');
        
        // Buscar usuários com patrocinador
        $users = User::whereNotNull('sponsor_id')->get();
        
        if ($users->isEmpty()) {
            $this->command->warn('⚠️ Nenhum usuário com patrocinador encontrado.');
            return;
        }
        
        $this->command->info("👥 Encontrados {$users->count()} usuários com patrocinador");
        
        foreach ($users as $user) {
            $this->showUplines($user);
        }
    }
    
    /**
     * Exibe uplines de um usuário
     */
    private function showUplines(User $user): void
    {
        $this->command->info("👤 {$user->name} (ID: {$user->id})");
        
        $current = $user;
        $level = 1;
        
        while ($current->sponsor_id && $level <= 3) {
            $sponsor = User::find($current->sponsor_id);
            
            if (!$sponsor) {
                break;
            }
            
            $this->command->info("   ↳ Nível {$level}: {$sponsor->name} (ID: {$sponsor->id})");
            
            $current = $sponsor;
            $level++;
        }
        
        if ($level == 1) {
            $this->command->warn("   ⚠️ Patrocinador não encontrado");
        }
    }
}
