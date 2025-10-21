<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Models\Raffle;
use App\Models\RaffleTicket;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WalletTicket;
use App\Services\Customer\RaffleTicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RaffleTicketServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RaffleTicketService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RaffleTicketService;

        // Criar pool de tickets para testes
        for ($i = 1; $i <= 100; $i++) {
            Ticket::create(['number' => str_pad($i, 7, '0', STR_PAD_LEFT)]);
        }
    }

    /**
     * Test service can apply tickets to raffle successfully
     */
    public function test_service_can_apply_tickets_to_raffle_successfully(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $plan = Plan::factory()->create([
            'price' => 100,
        ]);

        $walletTicket = WalletTicket::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'ticket_level' => 2,
            'total_tickets' => 10,
            'status' => 'active',
        ]);

        $raffle = Raffle::factory()->create([
            'status' => 'active',
            'min_ticket_level' => 1,
            'max_tickets_per_user' => 10,
            'tickets_required' => 100,
        ]);

        $result = $this->service->applyTicketsToRaffle($user, $raffle, 5);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('applied_tickets', $result);
        $this->assertArrayHasKey('remaining_tickets', $result);
        $this->assertCount(5, $result['applied_tickets']);
        $this->assertEquals(5, $result['remaining_tickets']);

        // Verificar que wallet foi decrementado
        $walletTicket->refresh();
        $this->assertEquals(5, $walletTicket->total_tickets);

        // Verificar raffle_tickets criados
        $this->assertDatabaseCount('raffle_tickets', 5);
    }

    /**
     * Test service throws exception when user has no tickets
     */
    public function test_service_throws_exception_when_user_has_no_tickets(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $raffle = Raffle::factory()->create([
            'status' => 'active',
            'min_ticket_level' => 1,
            'max_tickets_per_user' => 10,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Você não possui tickets do nível mínimo exigido');

        $this->service->applyTicketsToRaffle($user, $raffle, 5);
    }

    /**
     * Test service throws exception when user has insufficient tickets
     */
    public function test_service_throws_exception_when_user_has_insufficient_tickets(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $plan = Plan::factory()->create();

        WalletTicket::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'ticket_level' => 1,
            'total_tickets' => 3,
            'status' => 'active',
        ]);

        $raffle = Raffle::factory()->create([
            'status' => 'active',
            'min_ticket_level' => 1,
            'max_tickets_per_user' => 10,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Você não possui tickets suficientes');

        $this->service->applyTicketsToRaffle($user, $raffle, 5);
    }

    /**
     * Test service throws exception when raffle is not active
     */
    public function test_service_throws_exception_when_raffle_is_not_active(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $plan = Plan::factory()->create();

        WalletTicket::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'ticket_level' => 1,
            'total_tickets' => 10,
            'status' => 'active',
        ]);

        $raffle = Raffle::factory()->create([
            'status' => 'inactive',
            'min_ticket_level' => 1,
            'max_tickets_per_user' => 10,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Esta rifa não está ativa');

        $this->service->applyTicketsToRaffle($user, $raffle, 5);
    }

    /**
     * Test service throws exception when exceeding max tickets per user
     */
    public function test_service_throws_exception_when_exceeding_max_tickets_per_user(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $plan = Plan::factory()->create();

        WalletTicket::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'ticket_level' => 1,
            'total_tickets' => 20,
            'status' => 'active',
        ]);

        $raffle = Raffle::factory()->create([
            'status' => 'active',
            'min_ticket_level' => 1,
            'max_tickets_per_user' => 5,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Quantidade excede o limite');

        $this->service->applyTicketsToRaffle($user, $raffle, 10);
    }

    /**
     * Test service respects max tickets when user already has tickets
     */
    public function test_service_respects_max_tickets_when_user_already_has_tickets(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $plan = Plan::factory()->create();

        WalletTicket::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'ticket_level' => 1,
            'total_tickets' => 20,
            'status' => 'active',
        ]);

        $raffle = Raffle::factory()->create([
            'status' => 'active',
            'min_ticket_level' => 1,
            'max_tickets_per_user' => 10,
        ]);

        // Aplicar 7 tickets primeiro
        RaffleTicket::factory()->count(7)->create([
            'user_id' => $user->id,
            'raffle_id' => $raffle->id,
            'status' => 'pending',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Quantidade excede o limite');

        // Tentar aplicar mais 5 (total seria 12, excede o máximo de 10)
        $this->service->applyTicketsToRaffle($user, $raffle, 5);
    }

    /**
     * Test service can cancel pending tickets
     */
    public function test_service_can_cancel_pending_tickets(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $plan = Plan::factory()->create();

        $walletTicket = WalletTicket::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'ticket_level' => 1,
            'total_tickets' => 5,
            'status' => 'active',
        ]);

        $raffle = Raffle::factory()->create(['status' => 'active']);

        $raffleTickets = RaffleTicket::factory()->count(3)->create([
            'user_id' => $user->id,
            'raffle_id' => $raffle->id,
            'status' => 'pending',
        ]);

        $uuids = $raffleTickets->pluck('uuid')->toArray();

        $result = $this->service->cancelTicketsFromRaffle($user, $raffle, $uuids);

        $this->assertIsArray($result);
        $this->assertEquals(3, $result['canceled_count']);
        $this->assertEquals(8, $result['returned_tickets']);

        // Verificar que wallet foi incrementado
        $walletTicket->refresh();
        $this->assertEquals(8, $walletTicket->total_tickets);

        // Verificar soft delete
        $this->assertSoftDeleted('raffle_tickets', [
            'uuid' => $uuids[0],
        ]);
    }

    /**
     * Test service cannot cancel confirmed tickets
     */
    public function test_service_cannot_cancel_confirmed_tickets(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $raffle = Raffle::factory()->create(['status' => 'active']);

        $raffleTicket = RaffleTicket::factory()->create([
            'user_id' => $user->id,
            'raffle_id' => $raffle->id,
            'status' => 'confirmed',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('não puderam ser cancelados');

        $this->service->cancelTicketsFromRaffle($user, $raffle, [$raffleTicket->uuid]);
    }

    /**
     * Test service can get user tickets in raffle
     */
    public function test_service_can_get_user_tickets_in_raffle(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $raffle = Raffle::factory()->create(['status' => 'active']);

        RaffleTicket::factory()->count(5)->create([
            'user_id' => $user->id,
            'raffle_id' => $raffle->id,
            'status' => 'pending',
        ]);

        RaffleTicket::factory()->count(3)->create([
            'user_id' => $user->id,
            'raffle_id' => $raffle->id,
            'status' => 'confirmed',
        ]);

        $result = $this->service->getUserTicketsInRaffle($user, $raffle);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('tickets', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
        $this->assertEquals(8, $result['total']);
        $this->assertEquals(5, $result['by_status']['pending']);
        $this->assertEquals(3, $result['by_status']['confirmed']);
    }

    /**
     * Test service uses tickets with appropriate level
     */
    public function test_service_uses_tickets_with_appropriate_level(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $plan1 = Plan::factory()->create();
        $plan2 = Plan::factory()->create();

        // Usuário tem tickets de nível 1 e 2
        WalletTicket::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan1->id,
            'ticket_level' => 1,
            'total_tickets' => 5,
            'status' => 'active',
        ]);

        WalletTicket::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan2->id,
            'ticket_level' => 2,
            'total_tickets' => 5,
            'status' => 'active',
        ]);

        $raffle = Raffle::factory()->create([
            'status' => 'active',
            'min_ticket_level' => 2,
            'max_tickets_per_user' => 10,
        ]);

        $result = $this->service->applyTicketsToRaffle($user, $raffle, 3);

        $this->assertCount(3, $result['applied_tickets']);

        // Verificar que usou tickets do nível 2
        $walletTicket = WalletTicket::where('user_id', $user->id)
            ->where('ticket_level', 2)
            ->first();

        $this->assertEquals(2, $walletTicket->total_tickets);
    }

    /**
     * Test service rollback on failure
     */
    public function test_service_rollback_on_failure(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $plan = Plan::factory()->create();

        $walletTicket = WalletTicket::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'ticket_level' => 1,
            'total_tickets' => 10,
            'status' => 'active',
        ]);

        $raffle = Raffle::factory()->create([
            'status' => 'active',
            'min_ticket_level' => 1,
            'max_tickets_per_user' => 10,
        ]);

        // Limpar o pool de tickets para forçar erro
        Ticket::query()->delete();

        try {
            $this->service->applyTicketsToRaffle($user, $raffle, 5);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            // Verificar que wallet não foi alterado (rollback)
            $walletTicket->refresh();
            $this->assertEquals(10, $walletTicket->total_tickets);

            // Verificar que nenhum raffle_ticket foi criado
            $this->assertDatabaseCount('raffle_tickets', 0);
        }
    }
}
