<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HealthController extends Controller
{
    /**
     * Health check endpoint
     */
    public function check(Request $request)
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now(),
            'version' => '1.0.0',
            'environment' => app()->environment(),
            'uptime' => time() - $_SERVER['REQUEST_TIME'] ?? 0,
        ]);
    }
}
