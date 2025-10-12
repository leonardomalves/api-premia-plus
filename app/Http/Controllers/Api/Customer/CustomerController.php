<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display the authenticated user's profile
     */
    public function show(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'user' => $user->load('sponsor'),
        ]);
    }

    /**
     * Get user's own network (sponsored users)
     */
    public function network(Request $request)
    {
        $user = $request->user();
        
        $network = User::where('sponsor_id', $user->id)
            ->with('sponsor')
            ->paginate(15);

        return response()->json([
            'network' => $network,
            'total_network' => User::where('sponsor_id', $user->id)->count(),
            'user_info' => [
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Get user's sponsor information
     */
    public function sponsor(Request $request)
    {
        $user = $request->user();
        
        if (!$user->sponsor_id) {
            return response()->json([
                'message' => 'Você não possui patrocinador',
            ], 404);
        }

        $sponsor = User::find($user->sponsor_id);

        return response()->json([
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
        ]);
    }

    /**
     * Get user's own statistics
     */
    public function statistics(Request $request)
    {
        $user = $request->user();
        
        $stats = [
            'total_network' => User::where('sponsor_id', $user->id)->count(),
            'active_network' => User::where('sponsor_id', $user->id)->where('status', 'active')->count(),
            'inactive_network' => User::where('sponsor_id', $user->id)->where('status', 'inactive')->count(),
            'suspended_network' => User::where('sponsor_id', $user->id)->where('status', 'suspended')->count(),
            'account_created_at' => $user->created_at,
            'last_login' => $user->updated_at,
            'user_info' => [
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
            ],
        ];

        return response()->json([
            'statistics' => $stats,
        ]);
    }

    /**
     * Get specific user's network (if user has permission)
     */
    public function userNetwork(Request $request, $uuid)
    {
        $targetUser = User::where('uuid', $uuid)->firstOrFail();
        $currentUser = $request->user();
        
        // Verificar se o usuário pode ver esta rede
        if ($currentUser->id !== $targetUser->id && $currentUser->role !== 'admin') {
            return response()->json([
                'message' => 'Acesso negado.',
            ], 403);
        }
        
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
     * Get specific user's sponsor (if user has permission)
     */
    public function userSponsor(Request $request, $uuid)
    {
        $targetUser = User::where('uuid', $uuid)->firstOrFail();
        $currentUser = $request->user();
        
        // Verificar se o usuário pode ver este patrocinador
        if ($currentUser->id !== $targetUser->id && $currentUser->role !== 'admin') {
            return response()->json([
                'message' => 'Acesso negado.',
            ], 403);
        }
        
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
     * Get specific user's statistics (if user has permission)
     */
    public function userStatistics(Request $request, $uuid)
    {
        $targetUser = User::where('uuid', $uuid)->firstOrFail();
        $currentUser = $request->user();
        
        // Verificar se o usuário pode ver estas estatísticas
        if ($currentUser->id !== $targetUser->id && $currentUser->role !== 'admin') {
            return response()->json([
                'message' => 'Acesso negado.',
            ], 403);
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

        return response()->json([
            'statistics' => $stats,
        ]);
    }
}
