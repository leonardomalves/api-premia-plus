<?php

declare(strict_types=1);

namespace App\Services\Customer;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
            throw new \Exception(__('app.profile.no_changes'));
        }

        $user->update($validated);

        return $user->fresh()->load('sponsor');
    }

    /**
     * Get user's own network (sponsored users)
     */
    public function network(User $user): array
    {
        Log::info('👥 ' . __('app.logs.searching_user_network'), ['user_uuid' => $user->uuid]);

        $cacheKey = "user_network_{$user->id}";
        
        $network = User::bySponsor($user->id)
            ->select(['id', 'uuid', 'name', 'email', 'status', 'created_at', 'sponsor_id'])
            ->with('sponsor:id,uuid,name,email')
            ->paginate(15);

        $totalNetwork = Cache::remember("user_network_count_{$user->id}", now()->addMinutes(10), 
            fn() => User::bySponsor($user->id)->count()
        );

        return [
            'network' => $network,
            'total_network' => $totalNetwork,
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
        Log::info('🔍 ' . __('app.logs.searching_sponsor'), ['user_uuid' => $user->uuid]);

        if (! $user->sponsor_id) {
            Log::warning('⚠️ ' . __('app.logs.user_no_sponsor_warning'), ['user_uuid' => $user->uuid]);
            throw new \Exception(__('app.sponsor.not_found'));
        }

        $sponsor = Cache::remember("user_sponsor_{$user->sponsor_id}", now()->addMinutes(30), 
            fn() => User::select(['id', 'uuid', 'name', 'email', 'phone', 'created_at'])
                       ->find($user->sponsor_id)
        );

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
        Log::info('📊 ' . __('app.logs.calculating_statistics'), ['user_uuid' => $user->uuid]);

        $cacheKey = "user_stats_{$user->id}";
        
        $networkStats = Cache::remember($cacheKey, now()->addMinutes(15), function() use ($user) {
            return User::bySponsor($user->id)
                ->selectRaw('
                    COUNT(*) as total_network,
                    SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_network,
                    SUM(CASE WHEN status = "inactive" THEN 1 ELSE 0 END) as inactive_network,
                    SUM(CASE WHEN status = "suspended" THEN 1 ELSE 0 END) as suspended_network
                ')
                ->first();
        });

        $commissionsEarned = Cache::remember("user_commissions_{$user->id}", now()->addMinutes(30),
            fn() => $user->commissions()->sum('amount')
        );

        $stats = [
            'total_network' => (int) $networkStats->total_network,
            'active_network' => (int) $networkStats->active_network,
            'inactive_network' => (int) $networkStats->inactive_network,
            'suspended_network' => (int) $networkStats->suspended_network,
            'account_created_at' => $user->created_at,
            'commissions_earned' => $commissionsEarned,
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
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('app.password.current_incorrect')],
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
            throw new \Exception(__('app.user.access_denied'));
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
            throw new \Exception(__('app.user.access_denied'));
        }

        if (! $targetUser->sponsor_id) {
            throw new \Exception(__('app.sponsor.user_no_sponsor'));
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
            throw new \Exception(__('app.user.access_denied'));
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
