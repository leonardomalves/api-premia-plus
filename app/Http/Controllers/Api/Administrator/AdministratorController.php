<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Administrator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Administrator\UserManagementService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdministratorController extends Controller
{
    protected UserManagementService $userService;

    public function __construct(UserManagementService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of users (admin only)
     */
    public function index(Request $request)
    {
        try {
            $filters = [
                'search' => $request->search,
                'role' => $request->role,
                'status' => $request->status,
                'sponsor_uuid' => $request->sponsor_uuid,
            ];

            $users = $this->userService->listUsers($filters, $request->per_page ?? 15);

            return response()->json([
                'users' => $users,
                'filters' => $filters,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao listar usuários',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified user (admin only)
     */
    public function show(Request $request, $uuid)
    {
        try {
            $user = $this->userService->findUserByUuid($uuid);

            return response()->json([
                'user' => $user,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Usuário não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar usuário',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created user (admin only)
     */
    public function store(Request $request)
    {
        try {
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

            $user = $this->userService->createUser($validated);

            return response()->json([
                'message' => 'Usuário criado com sucesso',
                'user' => $user,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao criar usuário',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified user (admin only)
     */
    public function update(Request $request, $uuid)
    {
        try {
            $user = $this->userService->findUserByUuid($uuid);

            $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => ['sometimes', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
                'username' => ['sometimes', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
                'phone' => 'sometimes|nullable|string|max:20',
                'role' => 'sometimes|in:user,admin,moderator,support,finance',
                'status' => 'sometimes|in:active,inactive,suspended',
                'sponsor_uuid' => 'sometimes|nullable|exists:users,uuid',
            ]);

            $updateData = $request->only(['name', 'email', 'username', 'phone', 'role', 'status', 'sponsor_uuid']);
            $updatedUser = $this->userService->updateUser($uuid, $updateData);

            return response()->json([
                'message' => 'Usuário atualizado com sucesso',
                'user' => $updatedUser,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Usuário não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao atualizar usuário',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified user (admin only)
     */
    public function destroy(Request $request, $uuid)
    {
        try {
            $this->userService->deleteUser($uuid, $request->user()->id);

            return response()->json([
                'message' => 'Usuário excluído com sucesso',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Usuário não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getMessage() === 'Você não pode excluir sua própria conta.' ? 403 : 500);
        }
    }

    /**
     * Get user's network (admin only)
     */
    public function network(Request $request, $uuid)
    {
        try {
            $networkData = $this->userService->getUserNetwork($uuid);

            return response()->json($networkData);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Usuário não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar rede do usuário',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's sponsor (admin only)
     */
    public function sponsor(Request $request, $uuid)
    {
        try {
            $sponsorData = $this->userService->getUserSponsor($uuid);

            return response()->json($sponsorData);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Usuário não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getMessage() === 'Usuário não possui patrocinador' ? 404 : 500);
        }
    }

    /**
     * Get user's statistics (admin only)
     */
    public function statistics(Request $request, $uuid)
    {
        try {
            $stats = $this->userService->getUserStatistics($uuid);

            return response()->json([
                'statistics' => $stats,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Usuário não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar estatísticas do usuário',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get system statistics (admin only)
     */
    public function systemStatistics(Request $request)
    {
        try {
            $stats = $this->userService->getSystemStatistics();

            return response()->json([
                'system_statistics' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar estatísticas do sistema',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk update users (admin only)
     */
    public function bulkUpdate(Request $request)
    {
        try {
            $request->validate([
                'user_uuids' => 'required|array',
                'user_uuids.*' => 'required|exists:users,uuid',
                'updates' => 'required|array',
                'updates.role' => 'sometimes|in:user,admin,moderator,support,finance',
                'updates.status' => 'sometimes|in:active,inactive,suspended',
            ]);

            $result = $this->userService->bulkUpdateUsers($request->user_uuids, $request->updates);

            return response()->json([
                'message' => "{$result['updated_count']} usuários atualizados com sucesso",
                'updated_count' => $result['updated_count'],
                'errors' => $result['errors'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro na atualização em massa',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove multiple users (admin only)
     */
    public function bulkDelete(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_uuids' => 'required|array|min:1',
                'user_uuids.*' => 'required|exists:users,uuid',
            ]);

            $result = $this->userService->bulkDeleteUsers($validated['user_uuids'], $request->user()->id);

            $status = $result['deleted_count'] > 0 ? 200 : 422;

            return response()->json([
                'message' => $result['deleted_count'] > 0 ? "{$result['deleted_count']} usuários excluídos com sucesso" : 'Nenhum usuário foi excluído',
                'deleted_count' => $result['deleted_count'],
                'errors' => $result['errors'],
            ], $status);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro na exclusão em massa',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export users data (admin only)
     */
    public function exportUsers(Request $request)
    {
        try {
            $filters = [
                'search' => $request->search,
                'role' => $request->role,
                'status' => $request->status,
                'sponsor_uuid' => $request->sponsor_uuid,
            ];

            $exportData = $this->userService->exportUsers($filters);

            return response()->json([
                'message' => 'Exportação gerada com sucesso',
                'total' => count($exportData),
                'users' => $exportData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao exportar usuários',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Dashboard overview (admin only)
     */
    public function dashboard(Request $request)
    {
        try {
            $dashboardData = $this->userService->getDashboardData();

            return response()->json($dashboardData);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao carregar dashboard',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
