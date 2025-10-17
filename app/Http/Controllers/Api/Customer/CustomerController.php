<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Services\Customer\CustomerService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    protected CustomerService $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    /**
     * Display the authenticated user's profile
     */
    public function show(Request $request): JsonResponse
    {
        $user = $this->customerService->show($request->user());
        
        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * Update authenticated user's profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'username' => ['sometimes', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        try {
            $updatedUser = $this->customerService->updateProfile($user, $validated);
            
            return response()->json([
                'message' => 'Perfil atualizado com sucesso',
                'user' => $updatedUser,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get user's own network (sponsored users)
     */
    public function network(Request $request): JsonResponse
    {
        $result = $this->customerService->network($request->user());
        
        return response()->json($result);
    }

    /**
     * Get user's sponsor information
     */
    public function sponsor(Request $request): JsonResponse
    {
        try {
            $result = $this->customerService->sponsor($request->user());
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Get user's own statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $result = $this->customerService->statistics($request->user());
        
        return response()->json($result);
    }

    /**
     * Change authenticated user's password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        try {
            $this->customerService->changePassword($user, $request->current_password, $request->password);
            
            return response()->json([
                'message' => 'Password changed successfully',
            ]);
        } catch (ValidationException $e) {
            throw $e; // Laravel handles this as 422 automatically
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get specific user's network (if user has permission)
     */
    public function userNetwork(Request $request, string $uuid): JsonResponse
    {
        try {
            $result = $this->customerService->userNetwork($request->user(), $uuid);
            
            return response()->json($result);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Usuário não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getMessage() === 'Acesso negado.' ? 403 : 500);
        }
    }

    /**
     * Get specific user's sponsor (if user has permission)
     */
    public function userSponsor(Request $request, string $uuid): JsonResponse
    {
        try {
            $result = $this->customerService->userSponsor($request->user(), $uuid);
            
            return response()->json($result);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Usuário não encontrado',
            ], 404);
        } catch (\Exception $e) {
            $statusCode = match($e->getMessage()) {
                'Acesso negado.' => 403,
                'Usuário não possui patrocinador' => 404,
                default => 500
            };
            
            return response()->json([
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Get specific user's statistics (if user has permission)
     */
    public function userStatistics(Request $request, string $uuid): JsonResponse
    {
        try {
            $result = $this->customerService->userStatistics($request->user(), $uuid);
            
            return response()->json($result);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Usuário não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getMessage() === 'Acesso negado.' ? 403 : 500);
        }
    }
}
