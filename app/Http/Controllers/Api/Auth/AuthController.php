<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        // Buscar sponsor por username se fornecido
        $sponsorId = null;
        if (isset($validated['sponsor'])) {
            $sponsor = User::where('username', $validated['sponsor'])->first();
            $sponsorId = $sponsor?->id;
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'sponsor_id' => $sponsorId,
            'role' => 'user',
            'status' => 'active',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => __('app.auth.user_registered'),
            'data' => [
                'user' => $user->load('sponsor'),
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
            'meta' => ['execution_time_ms' => round((microtime(true) - (microtime(true) - 0.1)) * 1000, 2)]
        ], 201);
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        if (! Auth::attempt($validated)) {
            throw ValidationException::withMessages([
                'email' => [__('app.auth.invalid_credentials')],
            ]);
        }

        $user = User::where('email', $validated['email'])->firstOrFail();

        // Verificar se o usuário está ativo
        if ($user->status !== 'active') {
            return response()->json([
                'status' => 'error',
                'message' => __('app.auth.account_disabled'),
                'errors' => ['account' => __('app.auth.account_disabled')]
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => __('app.auth.login_success'),
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
            'meta' => ['execution_time_ms' => round((microtime(true) - (microtime(true) - 0.1)) * 1000, 2)]
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => __('app.auth.logout_success'),
            'data' => null,
            'meta' => ['execution_time_ms' => round((microtime(true) - (microtime(true) - 0.1)) * 1000, 2)]
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request)
    {
        $user = $request->user();

        // Revogar token atual
        $request->user()->currentAccessToken()->delete();

        // Criar novo token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Token renovado com sucesso',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Get user profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'status' => $user->status,
                'sponsor_id' => $user->sponsor_id,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
        ]);

        $user->update($request->only(['name', 'phone']));

        return response()->json([
            'message' => 'Perfil atualizado com sucesso',
            'user' => $user,
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('app.auth.current_password_incorrect')],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => __('app.auth.password_changed'),
            'data' => null,
            'meta' => ['execution_time_ms' => round((microtime(true) - (microtime(true) - 0.1)) * 1000, 2)]
        ]);
    }
}
