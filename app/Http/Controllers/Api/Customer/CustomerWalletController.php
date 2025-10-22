<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\FinancialStatement;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerWalletController extends Controller
{
    /**
     * Exibe informações completas da wallet do usuário
     * 
     * GET /api/v1/customer/wallet
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Busca ou cria wallet do usuário
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'uuid' => \Illuminate\Support\Str::uuid(),
                'balance' => 0,
                'withdrawals' => 0,
                'blocked' => 0,
            ]
        );

        // Estatísticas de transações
        $totalCredits = FinancialStatement::where('user_id', $user->id)
            ->where('type', 'credit')
            ->sum('amount');
            
        $totalDebits = FinancialStatement::where('user_id', $user->id)
            ->where('type', 'debit')
            ->sum('amount');

        // Últimas 5 transações
        $recentTransactions = FinancialStatement::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($statement) {
                return [
                    'id' => $statement->id,
                    'uuid' => $statement->uuid,
                    'amount' => (float) $statement->amount,
                    'type' => $statement->type,
                    'description' => $statement->description,
                    'origin' => $statement->origin,
                    'created_at' => $statement->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Wallet carregada com sucesso',
            'data' => [
                'wallet' => [
                    'id' => $wallet->id,
                    'uuid' => $wallet->uuid,
                    'balance' => (float) $wallet->balance,
                    'blocked' => (float) $wallet->blocked,
                    'available_balance' => (float) $wallet->available_balance,
                    'withdrawals' => (float) $wallet->withdrawals,
                    'created_at' => $wallet->created_at->toIso8601String(),
                    'updated_at' => $wallet->updated_at->toIso8601String(),
                ],
                'statistics' => [
                    'total_credits' => (float) $totalCredits,
                    'total_debits' => (float) $totalDebits,
                    'net_balance' => (float) ($totalCredits - $totalDebits),
                ],
                'recent_transactions' => $recentTransactions,
            ]
        ], 200);
    }

    /**
     * Lista todos os extratos financeiros do usuário
     * 
     * GET /api/v1/customer/wallet/statements
     * 
     * Query Parameters:
     * - type: credit|debit (opcional)
     * - origin: order|raffle|commission|withdrawal|etc (opcional)
     * - date_from: YYYY-MM-DD (opcional)
     * - date_to: YYYY-MM-DD (opcional)
     * - per_page: int (padrão: 15)
     * - page: int (padrão: 1)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function statements(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = FinancialStatement::where('user_id', $user->id);

        // Filtros opcionais
        if ($request->has('type') && in_array($request->type, ['credit', 'debit'])) {
            $query->where('type', $request->type);
        }

        if ($request->has('origin')) {
            $query->where('origin', $request->origin);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Paginação
        $perPage = $request->get('per_page', 15);
        $statements = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Calcula totais do período filtrado
        $totalCredits = FinancialStatement::where('user_id', $user->id)
            ->where('type', 'credit')
            ->when($request->has('date_from'), function ($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->has('date_to'), function ($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->date_to);
            })
            ->sum('amount');

        $totalDebits = FinancialStatement::where('user_id', $user->id)
            ->where('type', 'debit')
            ->when($request->has('date_from'), function ($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->has('date_to'), function ($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->date_to);
            })
            ->sum('amount');

        return response()->json([
            'success' => true,
            'message' => 'Extratos carregados com sucesso',
            'data' => [
                'statements' => $statements->map(function ($statement) {
                    return [
                        'id' => $statement->id,
                        'uuid' => $statement->uuid,
                        'correlation_id' => $statement->correlation_id,
                        'amount' => (float) $statement->amount,
                        'type' => $statement->type,
                        'description' => $statement->description,
                        'origin' => $statement->origin,
                        'created_at' => $statement->created_at->toIso8601String(),
                    ];
                }),
                'pagination' => [
                    'current_page' => $statements->currentPage(),
                    'per_page' => $statements->perPage(),
                    'total' => $statements->total(),
                    'last_page' => $statements->lastPage(),
                ],
                'summary' => [
                    'total_credits' => (float) $totalCredits,
                    'total_debits' => (float) $totalDebits,
                    'net_balance' => (float) ($totalCredits - $totalDebits),
                ],
                'filters' => [
                    'type' => $request->get('type'),
                    'origin' => $request->get('origin'),
                    'date_from' => $request->get('date_from'),
                    'date_to' => $request->get('date_to'),
                ]
            ]
        ], 200);
    }

    /**
     * Lista histórico de transações agrupadas por tipo
     * 
     * GET /api/v1/customer/wallet/transactions
     * 
     * Query Parameters:
     * - date_from: YYYY-MM-DD (opcional)
     * - date_to: YYYY-MM-DD (opcional)
     * - per_page: int (padrão: 15)
     * - page: int (padrão: 1)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = FinancialStatement::where('user_id', $user->id);

        // Filtros de data
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Paginação
        $perPage = $request->get('per_page', 15);
        $transactions = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Agrupa transações por origem
        $groupedByOrigin = FinancialStatement::where('user_id', $user->id)
            ->when($request->has('date_from'), function ($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->has('date_to'), function ($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->date_to);
            })
            ->selectRaw('origin, type, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('origin', 'type')
            ->get()
            ->groupBy('origin')
            ->map(function ($items, $origin) {
                return [
                    'origin' => $origin,
                    'credits' => [
                        'count' => $items->where('type', 'credit')->first()->count ?? 0,
                        'total' => (float) ($items->where('type', 'credit')->first()->total ?? 0),
                    ],
                    'debits' => [
                        'count' => $items->where('type', 'debit')->first()->count ?? 0,
                        'total' => (float) ($items->where('type', 'debit')->first()->total ?? 0),
                    ],
                ];
            })
            ->values();

        // Agrupa por tipo
        $groupedByType = FinancialStatement::where('user_id', $user->id)
            ->when($request->has('date_from'), function ($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->has('date_to'), function ($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->date_to);
            })
            ->selectRaw('type, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->type => [
                        'count' => $item->count,
                        'total' => (float) $item->total,
                    ]
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Histórico de transações carregado com sucesso',
            'data' => [
                'transactions' => $transactions->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'uuid' => $transaction->uuid,
                        'correlation_id' => $transaction->correlation_id,
                        'amount' => (float) $transaction->amount,
                        'type' => $transaction->type,
                        'description' => $transaction->description,
                        'origin' => $transaction->origin,
                        'created_at' => $transaction->created_at->toIso8601String(),
                    ];
                }),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                    'last_page' => $transactions->lastPage(),
                ],
                'analytics' => [
                    'by_type' => $groupedByType,
                    'by_origin' => $groupedByOrigin,
                ],
                'filters' => [
                    'date_from' => $request->get('date_from'),
                    'date_to' => $request->get('date_to'),
                ]
            ]
        ], 200);
    }

    /**
     * Retorna apenas o saldo atual da wallet
     * 
     * GET /api/v1/customer/wallet/balance
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function balance(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'uuid' => \Illuminate\Support\Str::uuid(),
                'balance' => 0,
                'withdrawals' => 0,
                'blocked' => 0,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Saldo carregado com sucesso',
            'data' => [
                'balance' => (float) $wallet->balance,
                'blocked' => (float) $wallet->blocked,
                'available_balance' => (float) $wallet->available_balance,
                'withdrawals' => (float) $wallet->withdrawals,
            ]
        ], 200);
    }
}
