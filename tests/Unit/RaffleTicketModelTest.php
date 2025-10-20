<?php

namespace Tests\Unit;

use App\Models\Raffle;
use App\Models\RaffleTicket;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RaffleTicketModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test raffle ticket can be created with all required fields
     */
    public function test_raffle_ticket_can_be_created(): void
    {
        $user = User::factory()->create();
        $raffle = Raffle::factory()->create();
        $ticket = Ticket::create(['number' => '0000001']);

        $raffleTicket = RaffleTicket::create([
            'user_id' => $user->id,
            'raffle_id' => $raffle->id,
            'ticket_id' => $ticket->id,
            'status' => 'pending'
        ]);

        $this->assertDatabaseHas('raffle_tickets', [
            'user_id' => $user->id,
            'raffle_id' => $raffle->id,
            'ticket_id' => $ticket->id,
            'status' => 'pending'
        ]);

        $this->assertNotNull($raffleTicket->uuid);
    }

    /**
     * Test raffle ticket has user relationship
     */
    public function test_raffle_ticket_has_user_relationship(): void
    {
        $user = User::factory()->create();
        $raffle = Raffle::factory()->create();
        $ticket = Ticket::create(['number' => '0000001']);

        $raffleTicket = RaffleTicket::create([
            'user_id' => $user->id,
            'raffle_id' => $raffle->id,
            'ticket_id' => $ticket->id,
            'status' => 'pending'
        ]);

        $this->assertInstanceOf(User::class, $raffleTicket->user);
        $this->assertEquals($user->id, $raffleTicket->user->id);
    }

    /**
     * Test raffle ticket has raffle relationship
     */
    public function test_raffle_ticket_has_raffle_relationship(): void
    {
        $user = User::factory()->create();
        $raffle = Raffle::factory()->create();
        $ticket = Ticket::create(['number' => '0000001']);

        $raffleTicket = RaffleTicket::create([
            'user_id' => $user->id,
            'raffle_id' => $raffle->id,
            'ticket_id' => $ticket->id,
            'status' => 'pending'
        ]);

        $this->assertInstanceOf(Raffle::class, $raffleTicket->raffle);
        $this->assertEquals($raffle->id, $raffleTicket->raffle->id);
    }

    /**
     * Test raffle ticket has ticket relationship
     */
    public function test_raffle_ticket_has_ticket_relationship(): void
    {
        $user = User::factory()->create();
        $raffle = Raffle::factory()->create();
        $ticket = Ticket::create(['number' => '0000001']);

        $raffleTicket = RaffleTicket::create([
            'user_id' => $user->id,
            'raffle_id' => $raffle->id,
            'ticket_id' => $ticket->id,
            'status' => 'pending'
        ]);

        $this->assertInstanceOf(Ticket::class, $raffleTicket->ticket);
        $this->assertEquals($ticket->id, $raffleTicket->ticket->id);
        $this->assertEquals('0000001', $raffleTicket->ticket->number);
    }

    /**
     * Test status constants are defined
     */
    public function test_status_constants_are_defined(): void
    {
        $this->assertEquals('pending', RaffleTicket::STATUS_PENDING);
        $this->assertEquals('confirmed', RaffleTicket::STATUS_CONFIRMED);
        $this->assertEquals('winner', RaffleTicket::STATUS_WINNER);
    }

    /**
     * Test isPending method
     */
    public function test_is_pending_method(): void
    {
        $raffleTicket = RaffleTicket::factory()->create(['status' => 'pending']);
        $this->assertTrue($raffleTicket->isPending());

        $raffleTicket->status = 'confirmed';
        $this->assertFalse($raffleTicket->isPending());
    }

    /**
     * Test isConfirmed method
     */
    public function test_is_confirmed_method(): void
    {
        $raffleTicket = RaffleTicket::factory()->create(['status' => 'confirmed']);
        $this->assertTrue($raffleTicket->isConfirmed());

        $raffleTicket->status = 'pending';
        $this->assertFalse($raffleTicket->isConfirmed());
    }

    /**
     * Test isWinner method
     */
    public function test_is_winner_method(): void
    {
        $raffleTicket = RaffleTicket::factory()->create(['status' => 'winner']);
        $this->assertTrue($raffleTicket->isWinner());

        $raffleTicket->status = 'confirmed';
        $this->assertFalse($raffleTicket->isWinner());
    }

    /**
     * Test markAsConfirmed method
     */
    public function test_mark_as_confirmed_method(): void
    {
        $raffleTicket = RaffleTicket::factory()->create(['status' => 'pending']);
        
        $result = $raffleTicket->markAsConfirmed();

        $this->assertTrue($result);
        $this->assertEquals('confirmed', $raffleTicket->status);
        $this->assertDatabaseHas('raffle_tickets', [
            'id' => $raffleTicket->id,
            'status' => 'confirmed'
        ]);
    }

    /**
     * Test markAsWinner method
     */
    public function test_mark_as_winner_method(): void
    {
        $raffleTicket = RaffleTicket::factory()->create(['status' => 'confirmed']);
        
        $result = $raffleTicket->markAsWinner();

        $this->assertTrue($result);
        $this->assertEquals('winner', $raffleTicket->status);
        $this->assertDatabaseHas('raffle_tickets', [
            'id' => $raffleTicket->id,
            'status' => 'winner'
        ]);
    }

    /**
     * Test scopePending filters only pending tickets
     */
    public function test_scope_pending_filters_correctly(): void
    {
        RaffleTicket::factory()->count(3)->create(['status' => 'pending']);
        RaffleTicket::factory()->count(2)->create(['status' => 'confirmed']);
        RaffleTicket::factory()->count(1)->create(['status' => 'winner']);

        $pendingTickets = RaffleTicket::pending()->get();

        $this->assertCount(3, $pendingTickets);
        foreach ($pendingTickets as $ticket) {
            $this->assertEquals('pending', $ticket->status);
        }
    }

    /**
     * Test scopeConfirmed filters only confirmed tickets
     */
    public function test_scope_confirmed_filters_correctly(): void
    {
        RaffleTicket::factory()->count(3)->create(['status' => 'pending']);
        RaffleTicket::factory()->count(2)->create(['status' => 'confirmed']);
        RaffleTicket::factory()->count(1)->create(['status' => 'winner']);

        $confirmedTickets = RaffleTicket::confirmed()->get();

        $this->assertCount(2, $confirmedTickets);
        foreach ($confirmedTickets as $ticket) {
            $this->assertEquals('confirmed', $ticket->status);
        }
    }

    /**
     * Test scopeWinner filters only winner tickets
     */
    public function test_scope_winner_filters_correctly(): void
    {
        RaffleTicket::factory()->count(3)->create(['status' => 'pending']);
        RaffleTicket::factory()->count(2)->create(['status' => 'confirmed']);
        RaffleTicket::factory()->count(1)->create(['status' => 'winner']);

        $winnerTickets = RaffleTicket::winner()->get();

        $this->assertCount(1, $winnerTickets);
        $this->assertEquals('winner', $winnerTickets->first()->status);
    }

    /**
     * Test raffle ticket can be soft deleted
     */
    public function test_raffle_ticket_can_be_soft_deleted(): void
    {
        $raffleTicket = RaffleTicket::factory()->create();
        
        $raffleTicket->delete();

        $this->assertSoftDeleted('raffle_tickets', [
            'id' => $raffleTicket->id
        ]);
    }

    /**
     * Test raffle ticket can be restored
     */
    public function test_raffle_ticket_can_be_restored(): void
    {
        $raffleTicket = RaffleTicket::factory()->create();
        
        $raffleTicket->delete();
        $raffleTicket->restore();

        $this->assertDatabaseHas('raffle_tickets', [
            'id' => $raffleTicket->id,
            'deleted_at' => null
        ]);
    }

    /**
     * Test uuid is automatically generated
     */
    public function test_uuid_is_automatically_generated(): void
    {
        $raffleTicket = RaffleTicket::factory()->create();

        $this->assertNotNull($raffleTicket->uuid);
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/',
            $raffleTicket->uuid
        );
    }

    /**
     * Test factory creates valid raffle tickets
     */
    public function test_factory_creates_valid_raffle_tickets(): void
    {
        $raffleTickets = RaffleTicket::factory()->count(10)->create();

        $this->assertCount(10, $raffleTickets);

        foreach ($raffleTickets as $raffleTicket) {
            $this->assertNotNull($raffleTicket->user_id);
            $this->assertNotNull($raffleTicket->raffle_id);
            $this->assertNotNull($raffleTicket->ticket_id);
            $this->assertNotNull($raffleTicket->status);
            $this->assertContains($raffleTicket->status, ['pending', 'confirmed', 'winner']);
        }
    }

    /**
     * Test user can have multiple raffle tickets in same raffle
     */
    public function test_user_can_have_multiple_tickets_in_same_raffle(): void
    {
        $user = User::factory()->create();
        $raffle = Raffle::factory()->create();
        
        $ticket1 = Ticket::create(['number' => '0000001']);
        $ticket2 = Ticket::create(['number' => '0000002']);

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

        $userTickets = RaffleTicket::where('user_id', $user->id)
            ->where('raffle_id', $raffle->id)
            ->count();

        $this->assertEquals(2, $userTickets);
    }

    /**
     * Test raffle ticket status transitions
     */
    public function test_raffle_ticket_status_transitions(): void
    {
        $raffleTicket = RaffleTicket::factory()->create(['status' => 'pending']);

        // pending -> confirmed
        $raffleTicket->markAsConfirmed();
        $this->assertEquals('confirmed', $raffleTicket->status);

        // confirmed -> winner
        $raffleTicket->markAsWinner();
        $this->assertEquals('winner', $raffleTicket->status);
    }

    /**
     * Test casting timestamps
     */
    public function test_timestamps_are_cast_to_datetime(): void
    {
        $raffleTicket = RaffleTicket::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $raffleTicket->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $raffleTicket->updated_at);
    }

    /**
     * Test eager loading relationships
     */
    public function test_eager_loading_relationships(): void
    {
        RaffleTicket::factory()->count(5)->create();

        $raffleTickets = RaffleTicket::with(['user', 'raffle', 'ticket'])->get();

        $this->assertCount(5, $raffleTickets);

        foreach ($raffleTickets as $raffleTicket) {
            $this->assertTrue($raffleTicket->relationLoaded('user'));
            $this->assertTrue($raffleTicket->relationLoaded('raffle'));
            $this->assertTrue($raffleTicket->relationLoaded('ticket'));
        }
    }
}
