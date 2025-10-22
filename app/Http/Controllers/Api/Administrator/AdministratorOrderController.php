<?php

namespace App\Http\Controllers\Api\Administrator;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdministratorOrderController extends Controller
{
    /**
     * Lista todas as orders (admin)
     * 
     * GET /api/v1/administrator/orders
     * 
     * Query Parameters:
     * - status: pending|approved|rejected|cancelled (opcional)
     * - user_id: int (opcional - filtrar por usuário)
     * - plan_id: int (opcional - filtrar por plano)
     * - date_from: YYYY-MM-DD (opcional)
     * - date_to: YYYY-MM-DD (opcional)
     * - search: string (opcional - buscar por uuid, email do usuário ou nome do plano)
     * - per_page: int (padrão: 15)
     * - page: int (padrão: 1)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Query base
        $query = Order::with(['user', 'plan']);

        // Filtro por status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtro por usuário
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filtro por plano
        if ($request->has('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }

        // Filtro por data de criação
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Busca por texto (uuid, email do usuário, nome do plano)
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('email', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('plan', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Paginação
        $perPage = $request->get('per_page', 15);
        $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Estatísticas gerais
        $totalOrders = Order::count();
        $totalApproved = Order::where('status', 'approved')->count();
        $totalPending = Order::where('status', 'pending')->count();
        $totalRejected = Order::where('status', 'rejected')->count();
        $totalCancelled = Order::where('status', 'cancelled')->count();
        $totalRevenue = Order::where('status', 'approved')->sum('amount');

        return response()->json([
            'success' => true,
            'message' => 'Orders carregadas com sucesso',
            'data' => [
                'orders' => $orders->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'uuid' => $order->uuid,
                        'user' => [
                            'id' => $order->user->id,
                            'uuid' => $order->user->uuid,
                            'name' => $order->user->name,
                            'email' => $order->user->email,
                        ],
                        'plan' => [
                            'id' => $order->plan->id,
                            'uuid' => $order->plan->uuid,
                            'name' => $order->plan->name,
                            'price' => (float) $order->plan->price,
                        ],
                        'amount' => (float) $order->amount,
                        'currency' => $order->currency,
                        'status' => $order->status,
                        'payment_method' => $order->payment_method,
                        'paid_at' => $order->paid_at?->toIso8601String(),
                        'created_at' => $order->created_at->toIso8601String(),
                        'updated_at' => $order->updated_at->toIso8601String(),
                    ];
                }),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                ],
                'statistics' => [
                    'total_orders' => $totalOrders,
                    'total_approved' => $totalApproved,
                    'total_pending' => $totalPending,
                    'total_rejected' => $totalRejected,
                    'total_cancelled' => $totalCancelled,
                    'total_revenue' => (float) $totalRevenue,
                ],
                'filters' => [
                    'status' => $request->get('status'),
                    'user_id' => $request->get('user_id'),
                    'plan_id' => $request->get('plan_id'),
                    'date_from' => $request->get('date_from'),
                    'date_to' => $request->get('date_to'),
                    'search' => $request->get('search'),
                ]
            ]
        ], 200);
    }
}
