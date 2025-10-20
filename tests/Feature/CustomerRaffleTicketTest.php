<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Raffle;
use App\Models\RaffleTicket;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WalletTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerRaffleTicketTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar pool de tickets para testes
        for ($i = 1; $i <= 100; $i++) {
            Ticket::create(['number' => str_pad($i, 7, '0', STR_PAD_LEFT)]);
        }
    }

    /**
     * Test customer can apply tickets to raffle
     */
    public function test_customer_can_apply_tickets_to_raffle(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $plan = Plan::factory()->create([
            'grant_tickets' => 10,
            'ticket_level' => 1,
            'price' => 100
        ]);
        
        // Dar tickets ao usuário
        $walletTicket = WalletTicket::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'ticket_level' => 1,
            'total_tickets' => 10,
            'status' => 'active'
        ]);

        $raffle = Raffle::factory()->create([
            'status' => 'active',
            'min_ticket_level' => 1,
            'max_tickets_per_user' => 10,
            'tickets_required' => 100
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/customer/raffles/{$raffle->uuid}/tickets", [
            'quantity' => 5
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'applied_tickets' => [
                    '*' => [
                        'uuid',
                        'ticket_number',
                        'status',
                        'created_at'
                    ]
                ],
                'remaining_tickets'
            ]);

        $this->assertDatabaseHas('raffle_tickets', [
            'user_id' => $user->id,
            'raffle_id' => $raffle->id,
            'status' => 'pending'
        ]);

        // Verificar que wallet tickets foi decrementado
        $walletTicket->refresh();
        $this->assertEquals(5, $walletTicket->total_tickets);
    }

    /**
     * Test customer cannot apply more tickets than available
     */
    public function test_customer_cannot_apply_more_tickets_than_available(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $plan = Plan::factory()->create([
            'grant_tickets' => 5,
            'ticket_level' => 1
        ]);
        
        WalletTicket::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'ticket_level' => 1,
            'total_tickets' => 3,
            'status' => 'active'
        ]);

        $raffle = Raffle::factory()->create([
            'status' => 'active',
            'min_ticket_level' => 1,
            'max_tickets_per_user' => 10
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/customer/raffles/{$raffle->uuid}/tickets", [
            'quantity' => 5
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Você não possui tickets suficientes.'
            ]);
    }

    /**
     * Test customer cannot exceed max tickets per user
     */
    public function test_customer_cannot_exceed_max_tickets_per_user(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $plan = Plan::factory()->create([
            'grant_tickets' => 20,
            'ticket_level' => 1
        ]);
        
        WalletTicket::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'ticket_level' => 1,
            'total_tickets' => 20,
            'status' => 'active'
        ]);

        $raffle = Raffle::factory()->create([
            'status' => 'active',
            'min_ticket_level' => 1,
            'max_tickets_per_user' => 5
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/customer/raffles/{$raffle->uuid}/tickets", [
            'quantity' => 10
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Quantidade excede o limite de 5 tickets por usuário para esta rifa.'
            ]);
    }

    /**
     * Test customer cannot apply tickets with insufficient level
     */
    public function test_customer_cannot_apply_tickets_with_insufficient_level(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $plan = Plan::factory()->create([
            'grant_tickets' => 10,
            'ticket_level' => 1
        ]);
        
        WalletTicket::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'ticket_level' => 1,
            'total_tickets' => 10,
            'status' => 'active'
        ]);

        $raffle = Raffle::factory()->create([
            'status' => 'active',
            'min_ticket_level' => 3,
            'max_tickets_per_user' => 10
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/customer/raffles/{$raffle->uuid}/tickets", [
            'quantity' => 5
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Você não possui tickets do nível mínimo exigido (3).'
            ]);
    }

    /**
     * Test customer cannot apply tickets to inactive raffle
     */
    public function test_customer_cannot_apply_tickets_to_inactive_raffle(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $plan = Plan::factory()->create([
            'grant_tickets' => 10,
            'ticket_level' => 1
        ]);
        
        WalletTicket::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'ticket_level' => 1,
            'total_tickets' => 10,
            'status' => 'active'
        ]);

        $raffle = Raffle::factory()->create([
            'status' => 'inactive',
            'min_ticket_level' => 1,
            'max_tickets_per_user' => 10
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/customer/raffles/{$raffle->uuid}/tickets", [
            'quantity' => 5
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Esta rifa não está ativa.'
            ]);
    }

    /**
     * Test customer can list their tickets in a raffle
     */
    public function test_customer_can_list_their_tickets_in_raffle(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $raffle = Raffle::factory()->create(['status' => 'active']);
        
        // Criar tickets aplicados
        $ticket1 = Ticket::first();
        $ticket2 = Ticket::skip(1)->first();
        
        RaffleTicket::create([
            'user_id' => $user->id,
            'raffle_id' => $raffle->id,
            'ticket_id' => $ticket1->id,
            'status' => 'pending'
        ]);
        
        RaffleTicket::create([
            'user_id' => $user->id,
            'raffle_id' => $raffle->id,
            'ticket_id' => $ticket2->id,
            'status' => 'confirmed'
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/customer/raffles/{$raffle->uuid}/my-tickets");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'tickets' => [
                    '*' => [
                        'uuid',
                        'ticket_number',
                        'status',
                        'created_at'
                    ]
                ],
                'total',
                'by_status'
            ]);

        $this->assertCount(2, $response->json('tickets'));
    }

    /**
     * Test customer can cancel their pending tickets
     */
    public function test_customer_can_cancel_their_pending_tickets(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $plan = Plan::factory()->create([
            'grant_tickets' => 10,
            'ticket_level' => 1
        ]);
        
        $walletTicket = WalletTicket::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'ticket_level' => 1,
            'total_tickets' => 5,
            'status' => 'active'
        ]);

        $raffle = Raffle::factory()->create(['status' => 'active']);
        $ticket = Ticket::first();
        
        $raffleTicket = RaffleTicket::create([
            'user_id' => $user->id,
            'raffle_id' => $raffle->id,
            'ticket_id' => $ticket->id,
            'status' => 'pending'
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/customer/raffles/{$raffle->uuid}/tickets", [
            'raffle_ticket_uuids' => [$raffleTicket->uuid]
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Tickets cancelados com sucesso'
            ]);

        $this->assertDatabaseMissing('raffle_tickets', [
            'uuid' => $raffleTicket->uuid,
            'deleted_at' => null
        ]);

        // Verificar que wallet tickets foi incrementado
        $walletTicket->refresh();
        $this->assertEquals(6, $walletTicket->total_tickets);
    }

    /**
     * Test customer cannot cancel confirmed tickets
     */
    public function test_customer_cannot_cancel_confirmed_tickets(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $raffle = Raffle::factory()->create(['status' => 'active']);
        $ticket = Ticket::first();
        
        $raffleTicket = RaffleTicket::create([
            'user_id' => $user->id,
            'raffle_id' => $raffle->id,
            'ticket_id' => $ticket->id,
            'status' => 'confirmed'
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/customer/raffles/{$raffle->uuid}/tickets", [
            'raffle_ticket_uuids' => [$raffleTicket->uuid]
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Alguns tickets não puderam ser cancelados (já estão confirmados ou não pertencem a você).'
            ]);
    }

    /**
     * Test customer can list all available raffles
     */
    public function test_customer_can_list_all_available_raffles(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        
        Raffle::factory()->create(['status' => 'active']);
        Raffle::factory()->create(['status' => 'active']);
        Raffle::factory()->create(['status' => 'inactive']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/customer/raffles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'raffles' => [
                    'data' => [
                        '*' => [
                            'uuid',
                            'title',
                            'description',
                            'prize_value',
                            'status'
                        ]
                    ]
                ]
            ]);
    }

    /**
     * Test customer can view raffle details
     */
    public function test_customer_can_view_raffle_details(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $raffle = Raffle::factory()->create(['status' => 'active']);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/customer/raffles/{$raffle->uuid}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'raffle' => [
                    'uuid',
                    'title',
                    'description',
                    'prize_value',
                    'unit_ticket_value',
                    'tickets_required',
                    'min_ticket_level',
                    'max_tickets_per_user',
                    'status'
                ]
            ]);
    }

    /**
     * Test validation errors for applying tickets
     */
    public function test_validation_errors_for_applying_tickets(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $raffle = Raffle::factory()->create(['status' => 'active']);

        Sanctum::actingAs($user);

        // Test missing quantity
        $response = $this->postJson("/api/v1/customer/raffles/{$raffle->uuid}/tickets", []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);

        // Test invalid quantity (zero)
        $response = $this->postJson("/api/v1/customer/raffles/{$raffle->uuid}/tickets", [
            'quantity' => 0
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);

        // Test invalid quantity (negative)
        $response = $this->postJson("/api/v1/customer/raffles/{$raffle->uuid}/tickets", [
            'quantity' => -5
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);

        // Test invalid quantity (not integer)
        $response = $this->postJson("/api/v1/customer/raffles/{$raffle->uuid}/tickets", [
            'quantity' => 'five'
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    /**
     * Test unauthenticated user cannot access raffle ticket endpoints
     */
    public function test_unauthenticated_user_cannot_access_raffle_ticket_endpoints(): void
    {
        $raffle = Raffle::factory()->create(['status' => 'active']);

        $response = $this->postJson("/api/v1/customer/raffles/{$raffle->uuid}/tickets", [
            'quantity' => 5
        ]);
        $response->assertStatus(401);

        $response = $this->getJson("/api/v1/customer/raffles/{$raffle->uuid}/my-tickets");
        $response->assertStatus(401);

        $response = $this->deleteJson("/api/v1/customer/raffles/{$raffle->uuid}/tickets", [
            'raffle_ticket_uuids' => []
        ]);
        $response->assertStatus(401);
    }
}
