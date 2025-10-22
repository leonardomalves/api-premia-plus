<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Services\BusinessRules\CreateStatementService;
use App\Services\BusinessRules\ExecuteBusinessRule;
use App\Services\BusinessRules\PayCommissionService;
use App\Services\BusinessRules\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ExecuteBusinessRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_calls_all_services()
    {
        // Mock dos serviços
        $createStatementService = Mockery::mock(CreateStatementService::class);
        $payCommissionService = Mockery::mock(PayCommissionService::class);
        $walletService = Mockery::mock(WalletService::class);

        $order = Order::factory()->create();

        // Expectativas dos mocks
        $createStatementService->shouldReceive('processFinancialStatementOrder')
            ->once()
            ->with($order)
            ->andReturn(Mockery::mock('App\Models\FinancialStatement'));

        $payCommissionService->shouldReceive('processOrderCommissions')
            ->once()
            ->with($order)
            ->andReturn(['success' => true, 'commissions_created' => 2]);

        $walletService->shouldReceive('processWallet')
            ->once()
            ->with($order)
            ->andReturn(['success' => true, 'wallet_updated' => true]);

        // Instanciar serviço com mocks
        $executeBusinessRule = new ExecuteBusinessRule(
            $createStatementService,
            $payCommissionService,
            $walletService
        );

        // Executar
        $result = $executeBusinessRule->execute($order);

        // Verificar resultado
        $this->assertTrue($result['success']);
        $this->assertEquals($order->id, $result['order_id']);
        $this->assertArrayHasKey('financial_statement', $result['results']);
        $this->assertArrayHasKey('commissions', $result['results']);
        $this->assertArrayHasKey('wallet', $result['results']);
    }
}
