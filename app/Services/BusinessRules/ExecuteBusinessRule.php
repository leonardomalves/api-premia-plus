<?php

namespace App\Services\BusinessRules;

use App\Models\Order;

class ExecuteBusinessRule
{
    public function __construct(
        private CreateStatementService $createStatementService,
        private PayCommissionService $payCommissionService,
        private WalletTicketService $walletTicketService
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
        // $walletTicketResult = $this->walletTicketService->createWalletTicket($order);
        // $results['wallet_ticket'] = $walletTicketResult;

        

        return [
            'success' => true,
            'message' => 'Business rules executed successfully',
            'order_id' => $order->id,
            'results' => $results
        ];

    }
}
