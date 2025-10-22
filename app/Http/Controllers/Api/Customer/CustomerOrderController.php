<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    /**
     * Lista todas as compras do cliente autenticado
     * 
     * GET /api/v1/customer/orders
     * 
     * Query Parameters:
     * - status: pending|approved|rejected|cancelled (opcional)
     * - date_from: YYYY-MM-DD (opcional)
     * - date_to: YYYY-MM-DD (opcional)
     * - per_page: int (padrão: 15)
     * - page: int (padrão: 1)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Query base
        $query = Order::where('user_id', $user->id)
            ->with(['plan']);

        // Filtro por status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtro por data de criação
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Paginação
        $perPage = $request->get('per_page', 15);
        $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Estatísticas
        $totalOrders = Order::where('user_id', $user->id)->count();
        $totalApproved = Order::where('user_id', $user->id)->where('status', 'approved')->count();
        $totalPending = Order::where('user_id', $user->id)->where('status', 'pending')->count();
        $totalAmount = Order::where('user_id', $user->id)
            ->where('status', 'approved')
            ->sum('amount');

        return response()->json([
            'success' => true,
            'message' => 'Compras carregadas com sucesso',
            'data' => [
                'orders' => $orders->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'uuid' => $order->uuid,
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
                    'total_amount' => (float) $totalAmount,
                ],
                'filters' => [
                    'status' => $request->get('status'),
                    'date_from' => $request->get('date_from'),
                    'date_to' => $request->get('date_to'),
                ]
            ]
        ], 200);
    }

    /**
     * Exibe detalhes de uma compra específica
     * 
     * GET /api/v1/customer/orders/{uuid}
     * 
     * @param Request $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        $user = $request->user();

        // Busca a order do usuário autenticado
        $order = Order::where('user_id', $user->id)
            ->where('uuid', $uuid)
            ->with(['plan', 'cart'])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Compra não encontrada',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detalhes da compra carregados com sucesso',
            'data' => [
                'order' => [
                    'id' => $order->id,
                    'uuid' => $order->uuid,
                    'user_metadata' => $order->user_metadata,
                    'plan' => [
                        'id' => $order->plan->id,
                        'uuid' => $order->plan->uuid,
                        'name' => $order->plan->name,
                        'description' => $order->plan->description,
                        'price' => (float) $order->plan->price,
                        'type' => $order->plan->type,
                        'metadata' => $order->plan_metadata,
                    ],
                    'amount' => (float) $order->amount,
                    'currency' => $order->currency,
                    'status' => $order->status,
                    'payment_method' => $order->payment_method,
                    'payment_details' => $order->payment_details,
                    'paid_at' => $order->paid_at?->toIso8601String(),
                    'cart' => $order->cart ? [
                        'id' => $order->cart->id,
                        'uuid' => $order->cart->uuid,
                        'status' => $order->cart->status,
                    ] : null,
                    'created_at' => $order->created_at->toIso8601String(),
                    'updated_at' => $order->updated_at->toIso8601String(),
                ]
            ]
        ], 200);
    }
}
