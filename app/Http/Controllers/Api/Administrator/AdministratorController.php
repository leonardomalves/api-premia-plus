<?php

namespace App\Http\Controllers\Api\Administrator;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdministratorController extends Controller
{
    /**
     * Display a listing of users (admin only)
     */
    public function index(Request $request)
    {
        $users = User::with('sponsor')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%");
            })
            ->when($request->role, function ($query, $role) {
                $query->where('role', $role);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->sponsor_uuid, function ($query, $sponsorUuid) {
                $sponsor = User::where('uuid', $sponsorUuid)->first();
                if ($sponsor) {
                    $query->where('sponsor_id', $sponsor->id);
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'users' => $users,
            'filters' => [
                'search' => $request->search,
                'role' => $request->role,
                'status' => $request->status,
                'sponsor_uuid' => $request->sponsor_uuid,
            ],
        ]);
    }

    /**
     * Display the specified user (admin only)
     */
    public function show(Request $request, $uuid)
    {
        $user = User::with('sponsor')->where('uuid', $uuid)->firstOrFail();

        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * Store a newly created user (admin only)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'sometimes|nullable|string|max:20',
            'role' => 'sometimes|in:user,admin,moderator,support,finance',
            'status' => 'sometimes|in:active,inactive,suspended',
            'sponsor_uuid' => 'sometimes|nullable|exists:users,uuid',
        ]);

        $sponsorId = null;
        if (!empty($validated['sponsor_uuid'])) {
            $sponsor = User::where('uuid', $validated['sponsor_uuid'])->first();
            $sponsorId = $sponsor?->id;
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'] ?? 'user',
            'status' => $validated['status'] ?? 'active',
            'sponsor_id' => $sponsorId,
        ]);

        return response()->json([
            'message' => 'Usuário criado com sucesso',
            'user' => $user->load('sponsor'),
        ], 201);
    }

    /**
     * Update the specified user (admin only)
     */
    public function update(Request $request, $uuid)
    {
        $user = User::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'username' => ['sometimes', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'sometimes|nullable|string|max:20',
            'role' => 'sometimes|in:user,admin,moderator,support,finance',
            'status' => 'sometimes|in:active,inactive,suspended',
            'sponsor_uuid' => 'sometimes|nullable|exists:users,uuid',
        ]);

        // Buscar sponsor por UUID se fornecido
        $sponsorId = null;
        if ($request->sponsor_uuid) {
            $sponsor = User::where('uuid', $request->sponsor_uuid)->first();
            if ($sponsor) {
                $sponsorId = $sponsor->id;
            }
        }

        $updateData = $request->only(['name', 'email', 'username', 'phone', 'role', 'status']);
        if ($sponsorId !== null) {
            $updateData['sponsor_id'] = $sponsorId;
        }

        $user->update($updateData);

        return response()->json([
            'message' => 'Usuário atualizado com sucesso',
            'user' => $user->fresh()->load('sponsor'),
        ]);
    }

    /**
     * Remove the specified user (admin only)
     */
    public function destroy(Request $request, $uuid)
    {
        $user = User::where('uuid', $uuid)->firstOrFail();
        
        // Não permitir que o admin se exclua
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Você não pode excluir sua própria conta.',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'Usuário excluído com sucesso',
        ]);
    }

    /**
     * Get user's network (admin only)
     */
    public function network(Request $request, $uuid)
    {
        $targetUser = User::where('uuid', $uuid)->firstOrFail();
        
        $network = User::where('sponsor_id', $targetUser->id)
            ->with('sponsor')
            ->paginate(15);

        return response()->json([
            'network' => $network,
            'total_network' => User::where('sponsor_id', $targetUser->id)->count(),
            'target_user' => [
                'uuid' => $targetUser->uuid,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
            ],
        ]);
    }

    /**
     * Get user's sponsor (admin only)
     */
    public function sponsor(Request $request, $uuid)
    {
        $targetUser = User::where('uuid', $uuid)->firstOrFail();
        
        if (!$targetUser->sponsor_id) {
            return response()->json([
                'message' => 'Usuário não possui patrocinador',
            ], 404);
        }

        $sponsor = User::find($targetUser->sponsor_id);

        return response()->json([
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
        ]);
    }

    /**
     * Get user's statistics (admin only)
     */
    public function statistics(Request $request, $uuid)
    {
        $targetUser = User::where('uuid', $uuid)->firstOrFail();
        
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

        return response()->json([
            'statistics' => $stats,
        ]);
    }

    /**
     * Get system statistics (admin only)
     */
    public function systemStatistics(Request $request)
    {
        $stats = [
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

        return response()->json([
            'system_statistics' => $stats,
        ]);
    }

    /**
     * Bulk update users (admin only)
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'user_uuids' => 'required|array',
            'user_uuids.*' => 'required|exists:users,uuid',
            'updates' => 'required|array',
            'updates.role' => 'sometimes|in:user,admin,moderator,support,finance',
            'updates.status' => 'sometimes|in:active,inactive,suspended',
        ]);

        $userUuids = $request->user_uuids;
        $updates = $request->updates;

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
                $errors[] = "Erro ao atualizar usuário {$uuid}: " . $e->getMessage();
            }
        }

        return response()->json([
            'message' => "{$updatedCount} usuários atualizados com sucesso",
            'updated_count' => $updatedCount,
            'errors' => $errors,
        ]);
    }

    /**
     * Remove multiple users (admin only)
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'user_uuids' => 'required|array|min:1',
            'user_uuids.*' => 'required|exists:users,uuid',
        ]);

        $currentUser = $request->user();
        $uuids = collect($validated['user_uuids'])->unique();

        $users = User::whereIn('uuid', $uuids)->get();

        $deleted = 0;
        $errors = [];

        foreach ($users as $user) {
            if ($user->id === $currentUser->id) {
                $errors[] = 'Você não pode excluir sua própria conta.';
                continue;
            }

            try {
                $user->delete();
                $deleted++;
            } catch (\Exception $e) {
                $errors[] = "Erro ao excluir usuário {$user->uuid}: " . $e->getMessage();
            }
        }

        $status = $deleted > 0 ? 200 : 422;

        return response()->json([
            'message' => $deleted > 0 ? "{$deleted} usuários excluídos com sucesso" : 'Nenhum usuário foi excluído',
            'deleted_count' => $deleted,
            'errors' => $errors,
        ], $status);
    }

    /**
     * Export users data (admin only)
     */
    public function exportUsers(Request $request)
    {
        $users = User::with('sponsor')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->when($request->role, function ($query, $role) {
                $query->where('role', $role);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->sponsor_uuid, function ($query, $sponsorUuid) {
                $sponsor = User::where('uuid', $sponsorUuid)->first();
                if ($sponsor) {
                    $query->where('sponsor_id', $sponsor->id);
                }
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $export = $users->map(function ($user) {
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
        });

        return response()->json([
            'message' => 'Exportação gerada com sucesso',
            'total' => $export->count(),
            'users' => $export,
        ]);
    }

    /**
     * Dashboard overview (admin only)
     */
    public function dashboard(Request $request)
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

        return response()->json([
            'summary' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'inactive_users' => $inactiveUsers,
                'suspended_users' => $suspendedUsers,
                'new_users_last_30_days' => $newUsersLast30Days,
            ],
            'top_sponsors' => $topSponsors,
            'recent_users' => $recentUsers,
        ]);
    }
}
