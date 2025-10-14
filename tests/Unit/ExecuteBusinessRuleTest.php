<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\User;
use App\Models\WalletTicket;
use App\Services\BusinessRules\ExecuteBusinessRule;
use App\Services\BusinessRules\PayCommissionService;
use App\Services\BusinessRules\WalletTicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class ExecuteBusinessRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_calls_both_services()
    {
        // Mock dos serviços
        $payCommissionService = Mockery::mock(PayCommissionService::class);
        $walletTicketService = Mockery::mock(WalletTicketService::class);

        $order = Order::factory()->create();

        // Expectativas dos mocks
        $payCommissionService->shouldReceive('processOrderCommissions')
            ->once()
            ->with($order)
            ->andReturn(['success' => true, 'commissions_created' => 2]);

        $walletTicketService->shouldReceive('createWalletTicket')
            ->once()
            ->with($order)
            ->andReturn(WalletTicket::factory()->make());

        // Instanciar serviço com mocks
        $executeBusinessRule = new ExecuteBusinessRule(
            $payCommissionService,
            $walletTicketService
        );

        // Executar
        $result = $executeBusinessRule->execute($order);

        // Verificar resultado
        $this->assertTrue($result['success']);
        $this->assertEquals($order->id, $result['order_id']);
        $this->assertArrayHasKey('commissions', $result['results']);
        $this->assertArrayHasKey('wallet_ticket', $result['results']);
    }
}