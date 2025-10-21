<?php

namespace App\Services\BusinessRules;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UpLinesService
{
    /**
     * Profundidade configurável para buscar uplines
     */
    private $maxLevels = 3;

    /**
     * Busca uplines para uma order específica
     */
    public function run(Order $order): array
    {
        Log::info("🔍 Iniciando busca de uplines para order: {$order->uuid}");

        $user = $order->user;

        if (! $user) {
            Log::warning('⚠️ Usuário não encontrado na order.');

            return [
                'success' => false,
                'message' => 'Usuário não encontrado na order',
                'order' => $order,
                'uplines' => [],
            ];
        }

        Log::info("👤 Processando usuário: {$user->name} (ID: {$user->id})");

        $result = $this->findUplines($user);
        $result['order'] = $order;
        $result['success'] = true;

        Log::info('✅ Busca de uplines concluída!');

        return $result;
    }

    /**
     * Busca uplines para um usuário específico
     */
    public function findUplines(User $user): array
    {
        Log::info("👤 Usuário: {$user->name} (ID: {$user->id})");

        if (! $user->sponsor_id) {
            Log::warning('   ⚠️ Sem patrocinador');

            return [
                'user' => $user,
                'uplines' => [],
                'message' => 'Sem patrocinador',
            ];
        }

        // Buscar uplines com profundidade configurável
        $uplines = $this->getUplines($user, $this->maxLevels);

        if (empty($uplines)) {
            Log::warning('   ⚠️ Nenhum upline encontrado');

            return [
                'user' => $user,
                'uplines' => [],
                'message' => 'Nenhum upline encontrado',
            ];
        }

        Log::info('   📊 Uplines encontrados: '.count($uplines));

        $uplineData = [];
        foreach ($uplines as $level => $upline) {
            $uplineData[] = [
                'level' => $level + 1,
                'name' => $upline->name,
                'id' => $upline->id,
                'uuid' => $upline->uuid,
            ];
            Log::info('   ↳ Nível '.($level + 1).": {$upline->name} (ID: {$upline->id})");
        }

        return [
            'user' => $user,
            'uplines' => $uplineData,
            'total_uplines' => count($uplines),
        ];
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

            if (! $sponsor) {
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
    public function showStatistics(): array
    {
        $totalUsers = User::count();
        $usersWithSponsors = User::whereNotNull('sponsor_id')->count();

        Log::info('📊 Estatísticas gerais:');
        Log::info("   - Total de usuários: {$totalUsers}");
        Log::info("   - Usuários com patrocinador: {$usersWithSponsors}");

        // Contar usuários por nível de upline
        $levelDistribution = $this->countUplineLevels();

        return [
            'total_users' => $totalUsers,
            'users_with_sponsors' => $usersWithSponsors,
            'level_distribution' => $levelDistribution,
        ];
    }

    /**
     * Conta usuários por nível de upline
     */
    private function countUplineLevels(): array
    {
        Log::info('📊 Distribuição por níveis:');

        $users = User::whereNotNull('sponsor_id')->get();
        $levelCounts = [];

        foreach ($users as $user) {
            $uplines = $this->getUplines($user, $this->maxLevels);
            $levelCount = count($uplines);

            if (! isset($levelCounts[$levelCount])) {
                $levelCounts[$levelCount] = 0;
            }
            $levelCounts[$levelCount]++;
        }

        $distribution = [];
        for ($i = 1; $i <= $this->maxLevels; $i++) {
            $count = $levelCounts[$i] ?? 0;
            $distribution[$i] = $count;
            Log::info("   - {$i} nível(is): {$count} usuários");
        }

        return $distribution;
    }
}
