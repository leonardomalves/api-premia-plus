<?php

namespace Tests\Feature;

use App\Models\Raffle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdministratorRafflesTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test admin can list all raffles
     */
    public function test_admin_can_list_all_raffles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Raffle::factory(5)->create(['created_by' => $admin->id]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/administrator/raffles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'raffles' => [
                    'data' => [
                        '*' => [
                            'uuid',
                            'title',
                            'description',
                            'prize_value',
                            'operation_cost',
                            'unit_ticket_value',
                            'min_tickets_required',
                            
                            
                            'status',
                            'notes',
                            'created_at',
                            'updated_at',
                            'creator',
                        ],
                    ],
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
                'filters',
            ]);

        $this->assertCount(5, $response->json('raffles.data'));
    }

    /**
     * Test admin can filter raffles by status
     */
    public function test_admin_can_filter_raffles_by_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Raffle::factory(3)->create(['status' => 'active', 'created_by' => $admin->id]);
        Raffle::factory(2)->create(['status' => 'pending', 'created_by' => $admin->id]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/administrator/raffles?status=active');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('raffles.data'));

        foreach ($response->json('raffles.data') as $raffle) {
            $this->assertEquals('active', $raffle['status']);
        }
    }

    /**
     * Test admin can filter raffles by prize range
     */
    public function test_admin_can_filter_raffles_by_prize_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Raffle::factory()->create(['prize_value' => 500, 'created_by' => $admin->id]);
        Raffle::factory()->create(['prize_value' => 1000, 'created_by' => $admin->id]);
        Raffle::factory()->create(['prize_value' => 2000, 'created_by' => $admin->id]);
        Raffle::factory()->create(['prize_value' => 3000, 'created_by' => $admin->id]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/administrator/raffles?min_prize=750&max_prize=2500');

        $response->assertStatus(200);
        $raffles = $response->json('raffles.data');

        foreach ($raffles as $raffle) {
            $this->assertGreaterThanOrEqual(750, $raffle['prize_value']);
            $this->assertLessThanOrEqual(2500, $raffle['prize_value']);
        }
    }

    /**
     * Test admin can search raffles by title and description
     */
    public function test_admin_can_search_raffles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Raffle::factory()->create([
            'title' => 'iPhone 15 Pro Max',
            'description' => 'Latest smartphone from Apple',
            'created_by' => $admin->id,
        ]);
        Raffle::factory()->create([
            'title' => 'PlayStation 5',
            'description' => 'Gaming console with iPhone controller support',
            'created_by' => $admin->id,
        ]);
        Raffle::factory()->create([
            'title' => 'MacBook Pro',
            'description' => 'Professional laptop for developers',
            'created_by' => $admin->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/administrator/raffles?search=iPhone');

        $response->assertStatus(200);
        $raffles = $response->json('raffles.data');

        // Should find 2 raffles: one with "iPhone" in title, one with "iPhone" in description
        $this->assertCount(2, $raffles);
    }

    /**
     * Test admin can sort raffles
     */
    public function test_admin_can_sort_raffles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Raffle::factory()->create(['title' => 'C Raffle', 'prize_value' => 3000, 'created_by' => $admin->id]);
        Raffle::factory()->create(['title' => 'A Raffle', 'prize_value' => 1000, 'created_by' => $admin->id]);
        Raffle::factory()->create(['title' => 'B Raffle', 'prize_value' => 2000, 'created_by' => $admin->id]);

        Sanctum::actingAs($admin);

        // Test sort by title ascending
        $response = $this->getJson('/api/v1/administrator/raffles?sort_by=title&sort_order=asc');
        $response->assertStatus(200);
        $raffles = $response->json('raffles.data');
        $this->assertEquals('A Raffle', $raffles[0]['title']);
        $this->assertEquals('B Raffle', $raffles[1]['title']);
        $this->assertEquals('C Raffle', $raffles[2]['title']);

        // Test sort by prize_value descending
        $response = $this->getJson('/api/v1/administrator/raffles?sort_by=prize_value&sort_order=desc');
        $response->assertStatus(200);
        $raffles = $response->json('raffles.data');
        $this->assertEquals(3000, $raffles[0]['prize_value']);
        $this->assertEquals(2000, $raffles[1]['prize_value']);
        $this->assertEquals(1000, $raffles[2]['prize_value']);
    }

    /**
     * Test admin can view specific raffle details
     */
    public function test_admin_can_view_specific_raffle(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $raffle = Raffle::factory()->create(['created_by' => $admin->id]);

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/v1/administrator/raffles/{$raffle->uuid}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'raffle' => [
                    'uuid',
                    'title',
                    'description',
                    'prize_value',
                    'operation_cost',
                    'unit_ticket_value',
                    'min_tickets_required',
                    
                    
                    'status',
                    'notes',
                    'created_at',
                    'updated_at',
                    'creator',
                ],
            ])
            ->assertJson([
                'raffle' => [
                    'uuid' => $raffle->uuid,
                    'title' => $raffle->title,
                ],
            ]);
    }

    /**
     * Test admin can create new raffle
     */
    public function test_admin_can_create_new_raffle(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin);

        $raffleData = [
            'title' => 'iPhone 15 Pro Max Test',
            'description' => 'This is a test raffle for iPhone 15 Pro Max with 512GB storage',
            'prize_value' => 8999.99,
            'operation_cost' => 500.00,
            'unit_ticket_value' => 25.00,
            'liquidity_ratio' => 75.0,
            'liquid_value' => 6750.00,
            'min_tickets_required' => 400,
            'min_ticket_level' => 1,
            'max_tickets_per_user' => 10,
            'status' => 'pending',
            'notes' => 'Test raffle created by automated test',
        ];

        $response = $this->postJson('/api/v1/administrator/raffles', $raffleData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'raffle' => [
                    'uuid',
                    'title',
                    'description',
                    'prize_value',
                    'operation_cost',
                    'unit_ticket_value',
                    'min_tickets_required',
                    
                    
                    'status',
                    'notes',
                ],
            ])
            ->assertJson([
                'message' => 'Raffle criado com sucesso',
            ]);

        $this->assertDatabaseHas('raffles', [
            'title' => 'iPhone 15 Pro Max Test',
            'description' => 'This is a test raffle for iPhone 15 Pro Max with 512GB storage',
            'prize_value' => 8999.99,
            'created_by' => $admin->id,
        ]);
    }

    /**
     * Test admin cannot create raffle with invalid data
     */
    public function test_admin_cannot_create_raffle_with_invalid_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin);

        $invalidData = [
            'title' => '', // Required field empty
            'prize_value' => -100, // Negative value
            'operation_cost' => -50, // Negative cost
            'unit_ticket_value' => 0, // Zero ticket value
            'min_tickets_required' => 0, // Zero tickets
            'min_ticket_level' => -1, // Negative level
            'max_tickets_per_user' => 0, // Zero max tickets
            'status' => 'invalid_status', // Invalid status
        ];

        $response = $this->postJson('/api/v1/administrator/raffles', $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors',
            ])
            ->assertJson([
                'message' => 'Dados inválidos',
            ]);
    }

    /**
     * Test admin cannot create raffle with duplicate title
     */
    public function test_admin_cannot_create_raffle_with_duplicate_title(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $existingRaffle = Raffle::factory()->create([
            'title' => 'Unique Raffle Title',
            'created_by' => $admin->id,
        ]);

        Sanctum::actingAs($admin);

        $raffleData = [
            'title' => 'Unique Raffle Title', // Duplicate title
            'description' => 'Another raffle description',
            'prize_value' => 1000,
            'operation_cost' => 100,
            'unit_ticket_value' => 10,
            'liquidity_ratio' => 70.0,
            'liquid_value' => 700.00,
            'min_tickets_required' => 100,
            'min_ticket_level' => 1,
            'max_tickets_per_user' => 5,
            'status' => 'pending',
        ];

        $response = $this->postJson('/api/v1/administrator/raffles', $raffleData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /**
     * Test admin can update existing raffle
     */
    public function test_admin_can_update_existing_raffle(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $raffle = Raffle::factory()->create([
            'title' => 'Original Raffle',
            'prize_value' => 1000,
            'created_by' => $admin->id,
        ]);

        Sanctum::actingAs($admin);

        $updateData = [
            'title' => 'Updated Raffle',
            'prize_value' => 1500,
            'description' => 'Updated description',
        ];

        $response = $this->putJson("/api/v1/administrator/raffles/{$raffle->uuid}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Raffle atualizado com sucesso',
            ]);

        $this->assertDatabaseHas('raffles', [
            'uuid' => $raffle->uuid,
            'title' => 'Updated Raffle',
            'prize_value' => 1500,
        ]);
    }

    /**
     * Test admin can delete raffle
     */
    public function test_admin_can_delete_raffle(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $raffle = Raffle::factory()->create(['created_by' => $admin->id]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/v1/administrator/raffles/{$raffle->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Raffle removido com sucesso',
            ]);

        $this->assertSoftDeleted('raffles', [
            'uuid' => $raffle->uuid,
        ]);
    }

    /**
     * Test admin can restore deleted raffle
     */
    public function test_admin_can_restore_deleted_raffle(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $raffle = Raffle::factory()->create(['created_by' => $admin->id]);
        $raffle->delete(); // Soft delete first

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/v1/administrator/raffles/{$raffle->uuid}/restore");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Raffle restaurado com sucesso',
            ]);

        $this->assertDatabaseHas('raffles', [
            'uuid' => $raffle->uuid,
            'deleted_at' => null,
        ]);
    }

    /**
     * Test admin can toggle raffle status
     */
    public function test_admin_can_toggle_raffle_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin);

        // Test activating inactive raffle
        $inactiveRaffle = Raffle::factory()->create([
            'status' => 'inactive',
            'created_by' => $admin->id,
        ]);
        $response = $this->postJson("/api/v1/administrator/raffles/{$inactiveRaffle->uuid}/toggle-status");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Status alterado para active',
            ]);

        // Test deactivating active raffle
        $activeRaffle = Raffle::factory()->create([
            'status' => 'active',
            'created_by' => $admin->id,
        ]);
        $response = $this->postJson("/api/v1/administrator/raffles/{$activeRaffle->uuid}/toggle-status");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Status alterado para inactive',
            ]);
    }

    /**
     * Test admin can get raffle statistics
     */
    public function test_admin_can_get_raffle_statistics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Clear existing raffles and create controlled test data
        Raffle::query()->delete();

        Raffle::factory(3)->create([
            'status' => 'active',
            'prize_value' => 1000,
            'created_by' => $admin->id,
        ]);
        Raffle::factory(2)->create([
            'status' => 'pending',
            'prize_value' => 500,
            'created_by' => $admin->id,
        ]);
        Raffle::factory(1)->create([
            'status' => 'inactive',
            'prize_value' => 2000,
            'created_by' => $admin->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/administrator/raffles/statistics/overview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'statistics' => [
                    'total_raffles',
                    'active_raffles',
                    'pending_raffles',
                    'inactive_raffles',
                    'cancelled_raffles',
                    'total_prize_value',
                    'avg_prize_value',
                    'recent_raffles',
                ],
            ]);

        $stats = $response->json('statistics');
        $this->assertEquals(6, $stats['total_raffles']);
        $this->assertEquals(3, $stats['active_raffles']);
        $this->assertEquals(2, $stats['pending_raffles']);
        $this->assertEquals(1, $stats['inactive_raffles']);
        $this->assertEquals(3000, $stats['total_prize_value']); // 3 active × 1000
        $this->assertEquals(1000, $stats['avg_prize_value']);
    }

    /**
     * Test raffle validation rules for required fields
     */
    public function test_raffle_validation_rules_for_required_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/administrator/raffles', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'title',
                'description',
                'prize_value',
                'operation_cost',
                'unit_ticket_value',
                'min_tickets_required',
                
                
            ]);
    }

    /**
     * Test raffle validation for numeric constraints
     */
    public function test_raffle_validation_for_numeric_constraints(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin);

        $invalidData = [
            'title' => 'Test Raffle',
            'description' => 'Test description',
            'prize_value' => -100,           // Should be >= 0.01
            'operation_cost' => -50,         // Should be >= 0
            'unit_ticket_value' => 0,        // Should be >= 0.01
            'min_tickets_required' => 0,         // Should be >= 1
            'min_ticket_level' => 0,         // Should be >= 1 (based on controller)
            'max_tickets_per_user' => 0,     // Should be >= 1
            'status' => 'active',
        ];

        $response = $this->postJson('/api/v1/administrator/raffles', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'prize_value',
                'unit_ticket_value',
                'min_tickets_required',
                
                
            ]);
    }

    /**
     * Test raffle validation for maximum constraints
     */
    public function test_raffle_validation_for_maximum_constraints(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin);

        $invalidData = [
            'title' => str_repeat('A', 300),     // Should be max 255
            'description' => str_repeat('B', 1100), // Should be max 1000
            'prize_value' => 9999999.99,         // Should be max 999999.99
            'operation_cost' => 9999999.99,      // Should be max 999999.99
            'unit_ticket_value' => 9999.99,      // Should be max 999.99
            'min_tickets_required' => 10000000,      // Should be max 1000000
            'min_ticket_level' => 150,           // Should be max 100
            'max_tickets_per_user' => 10000,     // Should be max 1000
            'notes' => str_repeat('C', 2100),    // Should be max 2000
            'status' => 'active',
        ];

        $response = $this->postJson('/api/v1/administrator/raffles', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'title',
                'description',
                'prize_value',
                'operation_cost',
                'unit_ticket_value',
                'min_tickets_required',
                
                
                'notes',
            ]);
    }

    /**
     * Test admin handles non-existent raffle UUIDs
     */
    public function test_admin_handles_non_existent_raffle_uuids(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $fakeUuid = 'fake-uuid-that-does-not-exist';

        Sanctum::actingAs($admin);

        // Test show
        $response = $this->getJson("/api/v1/administrator/raffles/{$fakeUuid}");
        $response->assertStatus(404)
            ->assertJson(['message' => 'Raffle não encontrado']);

        // Test update
        $response = $this->putJson("/api/v1/administrator/raffles/{$fakeUuid}", ['title' => 'Updated']);
        $response->assertStatus(404);

        // Test delete
        $response = $this->deleteJson("/api/v1/administrator/raffles/{$fakeUuid}");
        $response->assertStatus(404);

        // Test restore
        $response = $this->postJson("/api/v1/administrator/raffles/{$fakeUuid}/restore");
        $response->assertStatus(404);

        // Test toggle status
        $response = $this->postJson("/api/v1/administrator/raffles/{$fakeUuid}/toggle-status");
        $response->assertStatus(404);
    }

    /**
     * Test non-admin cannot access raffle endpoints
     */
    public function test_non_admin_cannot_access_raffle_endpoints(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $raffle = Raffle::factory()->create(['created_by' => $admin->id]);

        Sanctum::actingAs($customer);

        // Test all endpoints
        $endpoints = [
            ['method' => 'get', 'url' => '/api/v1/administrator/raffles'],
            ['method' => 'get', 'url' => "/api/v1/administrator/raffles/{$raffle->uuid}"],
            ['method' => 'post', 'url' => '/api/v1/administrator/raffles', 'data' => ['title' => 'Test']],
            ['method' => 'put', 'url' => "/api/v1/administrator/raffles/{$raffle->uuid}", 'data' => ['title' => 'Updated']],
            ['method' => 'delete', 'url' => "/api/v1/administrator/raffles/{$raffle->uuid}"],
            ['method' => 'post', 'url' => "/api/v1/administrator/raffles/{$raffle->uuid}/restore"],
            ['method' => 'post', 'url' => "/api/v1/administrator/raffles/{$raffle->uuid}/toggle-status"],
            ['method' => 'get', 'url' => '/api/v1/administrator/raffles/statistics/overview'],
        ];

        foreach ($endpoints as $endpoint) {
            $method = $endpoint['method'].'Json';
            $data = $endpoint['data'] ?? [];

            $response = $this->$method($endpoint['url'], $data);
            $response->assertStatus(403);
        }
    }

    /**
     * Test unauthenticated user cannot access raffle endpoints
     */
    public function test_unauthenticated_user_cannot_access_raffle_endpoints(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $raffle = Raffle::factory()->create(['created_by' => $admin->id]);

        $endpoints = [
            ['method' => 'get', 'url' => '/api/v1/administrator/raffles'],
            ['method' => 'get', 'url' => "/api/v1/administrator/raffles/{$raffle->uuid}"],
            ['method' => 'post', 'url' => '/api/v1/administrator/raffles', 'data' => ['title' => 'Test']],
            ['method' => 'put', 'url' => "/api/v1/administrator/raffles/{$raffle->uuid}", 'data' => ['title' => 'Updated']],
            ['method' => 'delete', 'url' => "/api/v1/administrator/raffles/{$raffle->uuid}"],
            ['method' => 'post', 'url' => "/api/v1/administrator/raffles/{$raffle->uuid}/restore"],
            ['method' => 'post', 'url' => "/api/v1/administrator/raffles/{$raffle->uuid}/toggle-status"],
            ['method' => 'get', 'url' => '/api/v1/administrator/raffles/statistics/overview'],
        ];

        foreach ($endpoints as $endpoint) {
            $method = $endpoint['method'].'Json';
            $data = $endpoint['data'] ?? [];

            $response = $this->$method($endpoint['url'], $data);
            $response->assertStatus(401);
        }
    }

    /**
     * Test pagination functionality
     */
    public function test_pagination_functionality(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Raffle::factory(25)->create(['created_by' => $admin->id]); // Create more than default per_page

        Sanctum::actingAs($admin);

        // Test default pagination
        $response = $this->getJson('/api/v1/administrator/raffles');
        $response->assertStatus(200);

        $pagination = $response->json('raffles');
        $this->assertEquals(1, $pagination['current_page']);
        $this->assertEquals(15, $pagination['per_page']); // Default per page
        $this->assertEquals(25, $pagination['total']);
        $this->assertEquals(2, $pagination['last_page']);

        // Test custom per_page
        $response = $this->getJson('/api/v1/administrator/raffles?per_page=10');
        $response->assertStatus(200);

        $pagination = $response->json('raffles');
        $this->assertEquals(10, $pagination['per_page']);
        $this->assertEquals(3, $pagination['last_page']);

        // Test specific page
        $response = $this->getJson('/api/v1/administrator/raffles?page=2&per_page=10');
        $response->assertStatus(200);

        $pagination = $response->json('raffles');
        $this->assertEquals(2, $pagination['current_page']);
    }

    /**
     * Test complex filtering combinations
     */
    public function test_complex_filtering_combinations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create specific test data
        Raffle::factory()->create([
            'title' => 'Premium iPhone 15 Pro',
            'status' => 'active',
            'prize_value' => 8000,
            'created_by' => $admin->id,
        ]);

        Raffle::factory()->create([
            'title' => 'Basic Samsung Galaxy',
            'status' => 'active',
            'prize_value' => 800,
            'created_by' => $admin->id,
        ]);

        Raffle::factory()->create([
            'title' => 'Premium MacBook Pro',
            'status' => 'pending',
            'prize_value' => 12000,
            'created_by' => $admin->id,
        ]);

        Sanctum::actingAs($admin);

        // Test multiple filters combined
        $response = $this->getJson('/api/v1/administrator/raffles?status=active&min_prize=5000&search=Premium');

        $response->assertStatus(200);
        $raffles = $response->json('raffles.data');

        // Should only return the Premium iPhone 15 Pro
        $this->assertCount(1, $raffles);
        $this->assertEquals('Premium iPhone 15 Pro', $raffles[0]['title']);
        $this->assertEquals('active', $raffles[0]['status']);
        $this->assertGreaterThanOrEqual(5000, $raffles[0]['prize_value']);
    }

    /**
     * Test raffle status validation
     */
    public function test_raffle_status_validation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin);

        $validStatuses = ['pending', 'active', 'inactive', 'cancelled'];

        foreach ($validStatuses as $status) {
            $raffleData = [
                'title' => "Test Raffle {$status}",
                'description' => 'Test description',
                'prize_value' => 1000,
                'operation_cost' => 100,
                'unit_ticket_value' => 10,
                'liquidity_ratio' => 75.0,
                'liquid_value' => 750.00,
                'min_tickets_required' => 100,
                'min_ticket_level' => 1,
                'max_tickets_per_user' => 10,
                'status' => $status,
            ];

            $response = $this->postJson('/api/v1/administrator/raffles', $raffleData);
            $response->assertStatus(201);
        }

        // Test invalid status
        $invalidData = [
            'title' => 'Invalid Status Raffle',
            'description' => 'Test description',
            'prize_value' => 1000,
            'operation_cost' => 100,
            'unit_ticket_value' => 10,
            'liquidity_ratio' => 75.0,
            'liquid_value' => 750.00,
            'min_tickets_required' => 100,
            'min_ticket_level' => 1,
            'max_tickets_per_user' => 10,
            'status' => 'invalid_status',
        ];

        $response = $this->postJson('/api/v1/administrator/raffles', $invalidData);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }
}
