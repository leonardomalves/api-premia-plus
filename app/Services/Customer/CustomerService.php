<?php

namespace App\Services\Customer;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    /**
     * Display the authenticated user's profile
     */
    public function show(User $user): User
    {
        return $user->load('sponsor');
    }

    /**
     * Update authenticated user's profile
     */
    public function updateProfile(User $user, array $validated): User
    {    
        if (empty($validated)) {
            throw new \Exception('Nenhuma alteração fornecida.');
        }

        $user->update($validated);

        return $user->fresh()->load('sponsor');
    }

    /**
     * Get user's own network (sponsored users)
     */
    public function network(User $user): array
    {
        $network = User::where('sponsor_id', $user->id)
            ->with('sponsor')
            ->paginate(15);

        return [
            'network' => $network,
            'total_network' => User::where('sponsor_id', $user->id)->count(),
            'user_info' => [
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ];
    }

    /**
     * Get user's sponsor information
     */
    public function sponsor(User $user): array
    {
        if (!$user->sponsor_id) {
            throw new \Exception('Você não possui patrocinador');
        }

        $sponsor = User::find($user->sponsor_id);

        return [
            'sponsor' => [
                'uuid' => $sponsor->uuid,
                'name' => $sponsor->name,
                'email' => $sponsor->email,
                'phone' => $sponsor->phone,
                'created_at' => $sponsor->created_at,
            ],
            'user_info' => [
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ];
    }

    /**
     * Get user's own statistics
     */
    public function statistics(User $user): array
    {
        $stats = [
            'total_network' => User::where('sponsor_id', $user->id)->count(),
            'active_network' => User::where('sponsor_id', $user->id)->where('status', 'active')->count(),
            'inactive_network' => User::where('sponsor_id', $user->id)->where('status', 'inactive')->count(),
            'suspended_network' => User::where('sponsor_id', $user->id)->where('status', 'suspended')->count(),
            'account_created_at' => $user->created_at,
            'commssions_earned' => $user->commissions()->sum('amount'), // Placeholder para futuras implementações
            'last_login' => $user->updated_at,
            'user_info' => [
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
            ],
        ];

        return ['statistics' => $stats];
    }

    /**
     * Change authenticated user's password
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['A senha atual está incorreta.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);
    }

    /**
     * Get specific user's network (if user has permission)
     */
    public function userNetwork(User $currentUser, string $uuid): array
    {
        $targetUser = User::where('uuid', $uuid)->firstOrFail();
        
        // Verificar se o usuário pode ver esta rede
        if ($currentUser->id !== $targetUser->id && $currentUser->role !== 'admin') {
            throw new \Exception('Acesso negado.');
        }
        
        $network = User::where('sponsor_id', $targetUser->id)
            ->with('sponsor')
            ->paginate(15);

        return [
            'network' => $network,
            'total_network' => User::where('sponsor_id', $targetUser->id)->count(),
            'target_user' => [
                'uuid' => $targetUser->uuid,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
            ],
        ];
    }

    /**
     * Get specific user's sponsor (if user has permission)
     */
    public function userSponsor(User $currentUser, string $uuid): array
    {
        $targetUser = User::where('uuid', $uuid)->firstOrFail();
        
        // Verificar se o usuário pode ver este patrocinador
        if ($currentUser->id !== $targetUser->id && $currentUser->role !== 'admin') {
            throw new \Exception('Acesso negado.');
        }
        
        if (!$targetUser->sponsor_id) {
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
     * Get specific user's statistics (if user has permission)
     */
    public function userStatistics(User $currentUser, string $uuid): array
    {
        $targetUser = User::where('uuid', $uuid)->firstOrFail();
        
        // Verificar se o usuário pode ver estas estatísticas
        if ($currentUser->id !== $targetUser->id && $currentUser->role !== 'admin') {
            throw new \Exception('Acesso negado.');
        }
        
        $stats = [
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

        return ['statistics' => $stats];
    }
}
