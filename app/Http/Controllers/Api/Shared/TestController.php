<?php

namespace App\Http\Controllers\Api\Shared;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TestController extends Controller
{
    /**
     * Test endpoint for API documentation
     */
    public function index(Request $request)
    {
        return response()->json([
            'message' => 'API Premia Plus funcionando!',
            'timestamp' => now(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'version' => '1.0.0',
        ]);
    }
}
