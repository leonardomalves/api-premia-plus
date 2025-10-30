<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiKey
{
    /**
     * Handle an incoming request.
     *
     * Validates the X-API-Key header against the configured APP_API_KEY.
     * This middleware is used for securing administrative endpoints and
     * internal service communications.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');
        $configuredKey = config('app.api_key');

        // Check if API key is configured
        if (empty($configuredKey)) {
            return response()->json([
                'status' => 'error',
                'message' => __('app.system.api_key_not_configured'),
                'errors' => ['api_key' => 'API key not configured on server']
            ], 500);
        }

        // Check if API key is provided
        if (empty($apiKey)) {
            return response()->json([
                'status' => 'error',
                'message' => __('app.system.api_key_required'),
                'errors' => ['api_key' => 'X-API-Key header is required']
            ], 401);
        }

        // Validate API key
        if (!hash_equals($configuredKey, $apiKey)) {
            return response()->json([
                'status' => 'error',
                'message' => __('app.system.api_key_invalid'),
                'errors' => ['api_key' => 'Invalid API key provided']
            ], 401);
        }

        return $next($request);
    }
}