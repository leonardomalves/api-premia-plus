<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ChangePasswordRequest;
use App\Http\Requests\Customer\UpdateProfileRequest;
use App\Services\Customer\CustomerService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $startTime = microtime(true);
        $user = $this->customerService->show($request->user());
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        return response()->json([
            'status' => 'success',
            'message' => __('app.profile.retrieved'),
            'data' => ['user' => $user],
            'meta' => ['execution_time_ms' => $executionTime]
        ]);
    }

    /**
     * Update authenticated user's profile
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        try {
            $startTime = microtime(true);
            $updatedUser = $this->customerService->updateProfile($user, $validated);
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            return response()->json([
                'status' => 'success',
                'message' => __('app.profile.updated'),
                'data' => ['user' => $updatedUser],
                'meta' => ['execution_time_ms' => $executionTime]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'errors' => ['validation' => $e->getMessage()]
            ], 422);
        }
    }

    /**
     * Get user's own network (sponsored users)
     */
    public function network(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        $result = $this->customerService->network($request->user());
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        return response()->json([
            'status' => 'success',
            'message' => __('app.network.retrieved'),
            'data' => $result,
            'meta' => ['execution_time_ms' => $executionTime]
        ]);
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
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        try {
            $startTime = microtime(true);
            $this->customerService->changePassword($user, $validated['current_password'], $validated['password']);
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            return response()->json([
                'status' => 'success',
                'message' => __('app.password.changed'),
                'data' => null,
                'meta' => ['execution_time_ms' => $executionTime]
            ]);
        } catch (ValidationException $e) {
            throw $e; // Laravel handles this as 422 automatically
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'errors' => ['password' => $e->getMessage()]
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

            return response()->json([
                'status' => 'success',
                'message' => __('app.network.retrieved'),
                'data' => $result
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('app.user.not_found'),
                'errors' => ['user' => __('app.user.not_found')]
            ], 404);
        } catch (\Exception $e) {
            $statusCode = $e->getMessage() === __('app.user.access_denied') ? 403 : 500;
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'errors' => ['access' => $e->getMessage()]
            ], $statusCode);
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
            $statusCode = match ($e->getMessage()) {
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
