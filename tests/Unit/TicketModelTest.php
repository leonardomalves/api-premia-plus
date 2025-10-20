<?php

namespace Tests\Unit;

use App\Models\Raffle;
use App\Models\RaffleTicket;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test ticket can be created with only number
     */
    public function test_ticket_can_be_created_with_only_number(): void
    {
        $ticket = Ticket::create([
            'number' => '0000001'
        ]);

        $this->assertDatabaseHas('tickets', [
            'number' => '0000001'
        ]);

        $this->assertEquals('0000001', $ticket->number);
    }

    /**
     * Test ticket number is unique
     */
    public function test_ticket_number_is_unique(): void
    {
        Ticket::create(['number' => '0000001']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Ticket::create(['number' => '0000001']);
    }

    /**
     * Test ticket has raffle_tickets relationship
     */
    public function test_ticket_has_raffle_tickets_relationship(): void
    {
        $ticket = Ticket::create(['number' => '0000001']);
        $user = User::factory()->create();
        $raffle = Raffle::factory()->create();

        RaffleTicket::create([
            'user_id' => $user->id,
            'raffle_id' => $raffle->id,
            'ticket_id' => $ticket->id,
            'status' => 'pending'
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $ticket->raffleTickets);
        $this->assertCount(1, $ticket->raffleTickets);
    }

    /**
     * Test ticket has raffles relationship through raffle_tickets
     */
    public function test_ticket_has_raffles_relationship(): void
    {
        $ticket = Ticket::create(['number' => '0000001']);
        $user = User::factory()->create();
        $raffle1 = Raffle::factory()->create();
        $raffle2 = Raffle::factory()->create();

        RaffleTicket::create([
            'user_id' => $user->id,
            'raffle_id' => $raffle1->id,
            'ticket_id' => $ticket->id,
            'status' => 'pending'
        ]);

        RaffleTicket::create([
            'user_id' => $user->id,
            'raffle_id' => $raffle2->id,
            'ticket_id' => $ticket->id,
            'status' => 'pending'
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $ticket->raffles);
        $this->assertCount(2, $ticket->raffles);
    }

    /**
     * Test isAvailable method returns true for unused ticket
     */
    public function test_is_available_returns_true_for_unused_ticket(): void
    {
        $ticket = Ticket::create(['number' => '0000001']);

        $this->assertTrue($ticket->isAvailable());
    }

    /**
     * Test isAvailable method returns false for used ticket
     */
    public function test_is_available_returns_false_for_used_ticket(): void
    {
        $ticket = Ticket::create(['number' => '0000001']);
        $user = User::factory()->create();
        $raffle = Raffle::factory()->create();

        RaffleTicket::create([
            'user_id' => $user->id,
            'raffle_id' => $raffle->id,
            'ticket_id' => $ticket->id,
            'status' => 'pending'
        ]);

        $this->assertFalse($ticket->isAvailable());
    }

    /**
     * Test isAppliedInRaffle method
     */
    public function test_is_applied_in_raffle_method(): void
    {
        $ticket = Ticket::create(['number' => '0000001']);
        $user = User::factory()->create();
        $raffle1 = Raffle::factory()->create();
        $raffle2 = Raffle::factory()->create();

        RaffleTicket::create([
            'user_id' => $user->id,
            'raffle_id' => $raffle1->id,
            'ticket_id' => $ticket->id,
            'status' => 'pending'
        ]);

        $this->assertTrue($ticket->isAppliedInRaffle($raffle1->id));
        $this->assertFalse($ticket->isAppliedInRaffle($raffle2->id));
    }

    /**
     * Test scopeAvailable filters only unused tickets
     */
    public function test_scope_available_filters_only_unused_tickets(): void
    {
        $ticket1 = Ticket::create(['number' => '0000001']);
        $ticket2 = Ticket::create(['number' => '0000002']);
        $ticket3 = Ticket::create(['number' => '0000003']);

        $user = User::factory()->create();
        $raffle = Raffle::factory()->create();

        // Aplicar ticket2 em uma rifa
        RaffleTicket::create([
            'user_id' => $user->id,
            'raffle_id' => $raffle->id,
            'ticket_id' => $ticket2->id,
            'status' => 'pending'
        ]);

        $availableTickets = Ticket::available()->get();

        $this->assertCount(2, $availableTickets);
        $this->assertTrue($availableTickets->contains($ticket1));
        $this->assertFalse($availableTickets->contains($ticket2));
        $this->assertTrue($availableTickets->contains($ticket3));
    }

    /**
     * Test scopeApplied filters only used tickets
     */
    public function test_scope_applied_filters_only_used_tickets(): void
    {
        $ticket1 = Ticket::create(['number' => '0000001']);
        $ticket2 = Ticket::create(['number' => '0000002']);
        $ticket3 = Ticket::create(['number' => '0000003']);

        $user = User::factory()->create();
        $raffle = Raffle::factory()->create();

        // Aplicar ticket1 e ticket3 em rifas
        RaffleTicket::create([
            'user_id' => $user->id,
            'raffle_id' => $raffle->id,
            'ticket_id' => $ticket1->id,
            'status' => 'pending'
        ]);

        RaffleTicket::create([
            'user_id' => $user->id,
            'raffle_id' => $raffle->id,
            'ticket_id' => $ticket3->id,
            'status' => 'confirmed'
        ]);

        $appliedTickets = Ticket::applied()->get();

        $this->assertCount(2, $appliedTickets);
        $this->assertTrue($appliedTickets->contains($ticket1));
        $this->assertFalse($appliedTickets->contains($ticket2));
        $this->assertTrue($appliedTickets->contains($ticket3));
    }

    /**
     * Test ticket can be soft deleted
     */
    public function test_ticket_can_be_soft_deleted(): void
    {
        $ticket = Ticket::create(['number' => '0000001']);
        
        $ticket->delete();

        $this->assertSoftDeleted('tickets', [
            'number' => '0000001'
        ]);

        // Verificar que ainda pode ser recuperado com withTrashed
        $deletedTicket = Ticket::withTrashed()->find($ticket->id);
        $this->assertNotNull($deletedTicket);
        $this->assertNotNull($deletedTicket->deleted_at);
    }

    /**
     * Test ticket can be restored after soft delete
     */
    public function test_ticket_can_be_restored_after_soft_delete(): void
    {
        $ticket = Ticket::create(['number' => '0000001']);
        
        $ticket->delete();
        $ticket->restore();

        $this->assertDatabaseHas('tickets', [
            'number' => '0000001',
            'deleted_at' => null
        ]);
    }

    /**
     * Test ticket factory generates valid tickets
     */
    public function test_ticket_factory_generates_valid_tickets(): void
    {
        $tickets = Ticket::factory()->count(10)->create();

        $this->assertCount(10, $tickets);

        foreach ($tickets as $ticket) {
            $this->assertNotNull($ticket->number);
            $this->assertEquals(7, strlen($ticket->number));
            $this->assertMatchesRegularExpression('/^\d{7}$/', $ticket->number);
        }
    }

    /**
     * Test ticket does not have user_id or raffle_id columns
     */
    public function test_ticket_does_not_have_direct_user_or_raffle_relationships(): void
    {
        $ticket = Ticket::create(['number' => '0000001']);

        // Verificar que o modelo não tem user_id ou raffle_id
        $this->assertArrayNotHasKey('user_id', $ticket->getAttributes());
        $this->assertArrayNotHasKey('raffle_id', $ticket->getAttributes());
        $this->assertArrayNotHasKey('ticket_level', $ticket->getAttributes());
        $this->assertArrayNotHasKey('status', $ticket->getAttributes());
    }

    /**
     * Test multiple tickets can be applied to same raffle
     */
    public function test_multiple_tickets_can_be_applied_to_same_raffle(): void
    {
        $ticket1 = Ticket::create(['number' => '0000001']);
        $ticket2 = Ticket::create(['number' => '0000002']);
        
        $user = User::factory()->create();
        $raffle = Raffle::factory()->create();

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
            'status' => 'pending'
        ]);

        $raffleTicketsCount = RaffleTicket::where('raffle_id', $raffle->id)->count();
        $this->assertEquals(2, $raffleTicketsCount);
    }

    /**
     * Test same ticket can be applied to different raffles
     */
    public function test_same_ticket_can_be_applied_to_different_raffles(): void
    {
        $ticket = Ticket::create(['number' => '0000001']);
        $user = User::factory()->create();
        $raffle1 = Raffle::factory()->create();
        $raffle2 = Raffle::factory()->create();

        RaffleTicket::create([
            'user_id' => $user->id,
            'raffle_id' => $raffle1->id,
            'ticket_id' => $ticket->id,
            'status' => 'pending'
        ]);

        RaffleTicket::create([
            'user_id' => $user->id,
            'raffle_id' => $raffle2->id,
            'ticket_id' => $ticket->id,
            'status' => 'pending'
        ]);

        $this->assertCount(2, $ticket->raffleTickets);
        $this->assertCount(2, $ticket->raffles);
    }
}
