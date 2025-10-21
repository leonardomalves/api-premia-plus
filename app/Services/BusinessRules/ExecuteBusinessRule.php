<?php

namespace App\Services\BusinessRules;

use App\Models\Order;

class ExecuteBusinessRule
{
    public function __construct(
        private CreateStatementService $createStatementService,
        private PayCommissionService $payCommissionService,
        private WalletService $walletService,
    ) {}

    public function execute(Order $order): array
    {
        $results = [];

        $createStatementResult = $this->createStatementService->processFinancialStatementOrder($order);
        $results['financial_statement'] = $createStatementResult;

        // Process commissions
        $commissionResult = $this->payCommissionService->processOrderCommissions($order);
        $results['commissions'] = $commissionResult;

        // Create wallet ticket
        $walletTicketResult = $this->walletService->processWallet($order);
        $results['wallet'] = $walletTicketResult;

        return [
            'success' => true,
            'message' => 'Business rules executed successfully',
            'order_id' => $order->id,
            'results' => $results,
        ];

    }
}
