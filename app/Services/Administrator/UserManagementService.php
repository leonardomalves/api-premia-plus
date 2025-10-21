<?php

namespace App\Services\Administrator;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserManagementService
{
    /**
     * Listar usuários com filtros e paginação
     */
    public function listUsers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::with('sponsor');

        // Aplicar filtros
        if (isset($filters['search']) && ! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if (isset($filters['role']) && ! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['status']) && ! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['sponsor_uuid']) && ! empty($filters['sponsor_uuid'])) {
            $sponsor = User::where('uuid', $filters['sponsor_uuid'])->first();
            if ($sponsor) {
                $query->where('sponsor_id', $sponsor->id);
            }
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Buscar usuário por UUID
     */
    public function findUserByUuid(string $uuid): User
    {
        return User::with('sponsor')->where('uuid', $uuid)->firstOrFail();
    }

    /**
     * Criar novo usuário
     */
    public function createUser(array $data): User
    {
        $sponsorId = null;
        if (! empty($data['sponsor_uuid'])) {
            $sponsor = User::where('uuid', $data['sponsor_uuid'])->first();
            $sponsorId = $sponsor?->id;
        }

        $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'] ?? 'user',
            'status' => $data['status'] ?? 'active',
            'sponsor_id' => $sponsorId,
        ];

        $user = User::create($userData);

        return $user->load('sponsor');
    }

    /**
     * Atualizar usuário
     */
    public function updateUser(string $uuid, array $data): User
    {
        $user = User::where('uuid', $uuid)->firstOrFail();

        // Buscar sponsor por UUID se fornecido
        $sponsorId = null;
        if (isset($data['sponsor_uuid'])) {
            $sponsor = User::where('uuid', $data['sponsor_uuid'])->first();
            if ($sponsor) {
                $sponsorId = $sponsor->id;
            }
        }

        $updateData = collect($data)->only(['name', 'email', 'username', 'phone', 'role', 'status'])->toArray();
        if ($sponsorId !== null) {
            $updateData['sponsor_id'] = $sponsorId;
        }

        $user->update($updateData);

        return $user->fresh()->load('sponsor');
    }

    /**
     * Deletar usuário
     */
    public function deleteUser(string $uuid, int $currentUserId): bool
    {
        $user = User::where('uuid', $uuid)->firstOrFail();

        // Não permitir que o admin se exclua
        if ($user->id === $currentUserId) {
            throw new \Exception('Você não pode excluir sua própria conta.');
        }

        return $user->delete();
    }

    /**
     * Obter rede de um usuário
     */
    public function getUserNetwork(string $uuid, int $perPage = 15): array
    {
        $targetUser = User::where('uuid', $uuid)->firstOrFail();

        $network = User::where('sponsor_id', $targetUser->id)
            ->with('sponsor')
            ->paginate($perPage);

        $totalNetwork = User::where('sponsor_id', $targetUser->id)->count();

        return [
            'network' => $network,
            'total_network' => $totalNetwork,
            'target_user' => [
                'uuid' => $targetUser->uuid,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
            ],
        ];
    }

    /**
     * Obter patrocinador de um usuário
     */
    public function getUserSponsor(string $uuid): array
    {
        $targetUser = User::where('uuid', $uuid)->firstOrFail();

        if (! $targetUser->sponsor_id) {
            throw new \Exception('Usuário não possui patrocinador');
        }

        $sponsor = User::find($targetUser->sponsor_id);

        return [
            'sponsor' => [
                'uuid' => $sponsor->uuid,
                'name' => $sponsor->name,
                'email' => $sponsor->email,
                'phone' => $sponsor->phone,
                'created_at' => $sponsor->created_at,
            ],
            'target_user' => [
                'uuid' => $targetUser->uuid,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
            ],
        ];
    }

    /**
     * Obter estatísticas de um usuário
     */
    public function getUserStatistics(string $uuid): array
    {
        $targetUser = User::where('uuid', $uuid)->firstOrFail();

        return [
            'total_network' => User::where('sponsor_id', $targetUser->id)->count(),
            'active_network' => User::where('sponsor_id', $targetUser->id)->where('status', 'active')->count(),
            'inactive_network' => User::where('sponsor_id', $targetUser->id)->where('status', 'inactive')->count(),
            'suspended_network' => User::where('sponsor_id', $targetUser->id)->where('status', 'suspended')->count(),
            'account_created_at' => $targetUser->created_at,
            'last_login' => $targetUser->updated_at,
            'user_info' => [
                'uuid' => $targetUser->uuid,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
                'role' => $targetUser->role,
                'status' => $targetUser->status,
            ],
        ];
    }

    /**
     * Obter estatísticas do sistema
     */
    public function getSystemStatistics(): array
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'inactive_users' => User::where('status', 'inactive')->count(),
            'suspended_users' => User::where('status', 'suspended')->count(),
            'users_by_role' => User::selectRaw('role, count(*) as count')
                ->groupBy('role')
                ->pluck('count', 'role'),
            'users_by_status' => User::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'users_with_sponsors' => User::whereNotNull('sponsor_id')->count(),
            'users_without_sponsors' => User::whereNull('sponsor_id')->count(),
        ];
    }

    /**
     * Atualização em massa de usuários
     */
    public function bulkUpdateUsers(array $userUuids, array $updates): array
    {
        $updatedCount = 0;
        $errors = [];

        foreach ($userUuids as $uuid) {
            try {
                $user = User::where('uuid', $uuid)->first();
                if ($user) {
                    $user->update($updates);
                    $updatedCount++;
                }
            } catch (\Exception $e) {
                $errors[] = "Erro ao atualizar usuário {$uuid}: ".$e->getMessage();
            }
        }

        return [
            'updated_count' => $updatedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Exclusão em massa de usuários
     */
    public function bulkDeleteUsers(array $userUuids, int $currentUserId): array
    {
        $users = User::whereIn('uuid', $userUuids)->get();

        $deleted = 0;
        $errors = [];

        foreach ($users as $user) {
            if ($user->id === $currentUserId) {
                $errors[] = 'Você não pode excluir sua própria conta.';

                continue;
            }

            try {
                $user->delete();
                $deleted++;
            } catch (\Exception $e) {
                $errors[] = "Erro ao excluir usuário {$user->uuid}: ".$e->getMessage();
            }
        }

        return [
            'deleted_count' => $deleted,
            'errors' => $errors,
        ];
    }

    /**
     * Exportar dados dos usuários
     */
    public function exportUsers(array $filters = []): array
    {
        $query = User::with('sponsor');

        // Aplicar os mesmos filtros da listagem
        if (isset($filters['search']) && ! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if (isset($filters['role']) && ! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['status']) && ! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['sponsor_uuid']) && ! empty($filters['sponsor_uuid'])) {
            $sponsor = User::where('uuid', $filters['sponsor_uuid'])->first();
            if ($sponsor) {
                $query->where('sponsor_id', $sponsor->id);
            }
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        return $users->map(function ($user) {
            return [
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'role' => $user->role,
                'status' => $user->status,
                'phone' => $user->phone,
                'sponsor_uuid' => $user->sponsor?->uuid,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];
        })->toArray();
    }

    /**
     * Obter dados do dashboard
     */
    public function getDashboardData(): array
    {
        $totalUsers = User::count();
        $activeUsers = User::where('status', 'active')->count();
        $inactiveUsers = User::where('status', 'inactive')->count();
        $suspendedUsers = User::where('status', 'suspended')->count();

        $newUsersLast30Days = User::where('created_at', '>=', now()->subDays(30))->count();

        $topSponsors = User::withCount('sponsored')
            ->orderBy('sponsored_count', 'desc')
            ->take(5)
            ->get(['uuid', 'name', 'email', 'sponsored_count']);

        $recentUsers = User::orderBy('created_at', 'desc')
            ->take(5)
            ->get(['uuid', 'name', 'email', 'role', 'status', 'created_at']);

        return [
            'summary' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'inactive_users' => $inactiveUsers,
                'suspended_users' => $suspendedUsers,
                'new_users_last_30_days' => $newUsersLast30Days,
            ],
            'top_sponsors' => $topSponsors,
            'recent_users' => $recentUsers,
        ];
    }
}
