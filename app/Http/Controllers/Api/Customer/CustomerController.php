<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Customer\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{


    protected $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    /**
     * Display the authenticated user's profile
     */
    public function show(Request $request)
    {
        return $this->customerService->show($request);
    }

    /**
     * Update authenticated user's profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'username' => ['sometimes', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        return $this->customerService->updateProfile($user, $validated);
    }

    /**
     * Get user's own network (sponsored users)
     */
    public function network(Request $request)
    {
        return $this->customerService->network($request);
    }

    /**
     * Get user's sponsor information
     */
    public function sponsor(Request $request)
    {
        return $this->customerService->sponsor($request);
    }

    /**
     * Get user's own statistics
     */
    public function statistics(Request $request)
    {
        return $this->customerService->statistics($request);
    }

    /**
     * Change authenticated user's password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        return $this->customerService->changePassword($request, $user);
    }

    /**
     * Get specific user's network (if user has permission)
     */
    public function userNetwork(Request $request, $uuid)
    {
        return $this->customerService->userNetwork($request, $uuid);
    }

    /**
     * Get specific user's sponsor (if user has permission)
     */
    public function userSponsor(Request $request, $uuid)
    {
        return $this->customerService->userSponsor($request, $uuid);
    }

    /**
     * Get specific user's statistics (if user has permission)
     */
    public function userStatistics(Request $request, $uuid)
    {
        return $this->customerService->userStatistics($request, $uuid);
    }
}
