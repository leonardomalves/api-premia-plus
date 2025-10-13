<?php

namespace App\Services\BusinessRules;

use App\Models\Order;

class ExecuteBusinessRule
{
    public function execute(Order $order) {

        $payCommission = new PayComissionService();
        $payCommission->processOrderCommissions($order);
        
        $walletTicket = new WalletTicketService();
        $walletTicket->createWalletTicket($order);
      

    }
}
