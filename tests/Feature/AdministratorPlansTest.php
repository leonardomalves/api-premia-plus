<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdministratorPlansTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test admin can list all plans
     */
    public function test_admin_can_list_all_plans(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Plan::factory(5)->create();
        
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/administrator/plans');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'plans' => [
                            '*' => [
                                'uuid',
                                'name',
                                'description',
                                'price',
                                'status',
                                'is_promotional',
                                'created_at',
                                'updated_at'
                            ]
                        ],
                        'pagination' => [
                            'current_page',
                            'per_page',
                            'total',
                            'last_page'
                        ]
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Planos listados com sucesso'
                ]);

        $this->assertCount(5, $response->json('data.plans'));
    }

    /**
     * Test admin can filter plans by status
     */
    public function test_admin_can_filter_plans_by_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Plan::factory(3)->create(['status' => 'active']);
        Plan::factory(2)->create(['status' => 'inactive']);
        
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/administrator/plans?status=active');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data.plans'));
        
        foreach ($response->json('data.plans') as $plan) {
            $this->assertEquals('active', $plan['status']);
        }
    }

    /**
     * Test admin can filter plans by promotional status
     */
    public function test_admin_can_filter_plans_by_promotional_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Plan::factory(3)->create(['is_promotional' => true]);
        Plan::factory(2)->create(['is_promotional' => false]);
        
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/administrator/plans?promotional=true');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data.plans'));
        
        foreach ($response->json('data.plans') as $plan) {
            $this->assertTrue($plan['is_promotional']);
        }
    }

    /**
     * Test admin can filter plans by price range
     */
    public function test_admin_can_filter_plans_by_price_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Plan::factory()->create(['price' => 50]);
        Plan::factory()->create(['price' => 100]);
        Plan::factory()->create(['price' => 200]);
        Plan::factory()->create(['price' => 300]);
        
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/administrator/plans?min_price=75&max_price=250');

        $response->assertStatus(200);
        $plans = $response->json('data.plans');
        
        foreach ($plans as $plan) {
            $this->assertGreaterThanOrEqual(75, $plan['price']);
            $this->assertLessThanOrEqual(250, $plan['price']);
        }
    }

    /**
     * Test admin can search plans by name and description
     */
    public function test_admin_can_search_plans(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Plan::factory()->create(['name' => 'Premium Plan', 'description' => 'Best plan available']);
        Plan::factory()->create(['name' => 'Basic Plan', 'description' => 'Simple starter plan']);
        Plan::factory()->create(['name' => 'Gold Plan', 'description' => 'Premium features included']);
        
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/administrator/plans?search=Premium');

        $response->assertStatus(200);
        $plans = $response->json('data.plans');
        
        // Should find 2 plans: one with "Premium" in name, one with "Premium" in description
        $this->assertCount(2, $plans);
    }

    /**
     * Test admin can sort plans
     */
    public function test_admin_can_sort_plans(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Plan::factory()->create(['name' => 'C Plan', 'price' => 300]);
        Plan::factory()->create(['name' => 'A Plan', 'price' => 100]);
        Plan::factory()->create(['name' => 'B Plan', 'price' => 200]);
        
        Sanctum::actingAs($admin);

        // Test sort by name ascending
        $response = $this->getJson('/api/v1/administrator/plans?sort_by=name&sort_order=asc');
        $response->assertStatus(200);
        $plans = $response->json('data.plans');
        $this->assertEquals('A Plan', $plans[0]['name']);
        $this->assertEquals('B Plan', $plans[1]['name']);
        $this->assertEquals('C Plan', $plans[2]['name']);

        // Test sort by price descending
        $response = $this->getJson('/api/v1/administrator/plans?sort_by=price&sort_order=desc');
        $response->assertStatus(200);
        $plans = $response->json('data.plans');
        $this->assertEquals(300, $plans[0]['price']);
        $this->assertEquals(200, $plans[1]['price']);
        $this->assertEquals(100, $plans[2]['price']);
    }

    /**
     * Test admin can view specific plan details
     */
    public function test_admin_can_view_specific_plan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $plan = Plan::factory()->create();
        
        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/v1/administrator/plans/{$plan->uuid}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'plan' => [
                            'uuid',
                            'name',
                            'description',
                            'price',
                            'status',
                            'commission_level_1',
                            'commission_level_2',
                            'commission_level_3',
                            'is_promotional',
                            'start_date',
                            'end_date',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Plano encontrado com sucesso',
                    'data' => [
                        'plan' => [
                            'uuid' => $plan->uuid,
                            'name' => $plan->name
                        ]
                    ]
                ]);
    }

    /**
     * Test admin can create new plan
     */
    public function test_admin_can_create_new_plan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Sanctum::actingAs($admin);

        $planData = [
            'name' => 'Test Plan',
            'description' => 'This is a test plan for unit testing',
            'price' => 99.99,
            'status' => 'active',
            'commission_level_1' => 10.5,
            'commission_level_2' => 7.5,
            'commission_level_3' => 5.0,
            'is_promotional' => false,
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31'
        ];

        $response = $this->postJson('/api/v1/administrator/plans', $planData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'plan' => [
                            'uuid',
                            'name',
                            'description',
                            'price',
                            'status',
                            'commission_level_1',
                            'commission_level_2',
                            'commission_level_3',
                            'is_promotional',
                            'start_date',
                            'end_date'
                        ]
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Plano criado com sucesso'
                ]);

        $this->assertDatabaseHas('plans', [
            'name' => 'Test Plan',
            'description' => 'This is a test plan for unit testing',
            'price' => 99.99
        ]);
    }

    /**
     * Test admin cannot create plan with invalid data
     */
    public function test_admin_cannot_create_plan_with_invalid_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Sanctum::actingAs($admin);

        $invalidData = [
            'name' => '', // Required field empty
            'price' => -10, // Negative price
            'commission_level_1' => 150, // Over 100%
            'status' => 'invalid_status' // Invalid status
        ];

        $response = $this->postJson('/api/v1/administrator/plans', $invalidData);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors'
                ])
                ->assertJson([
                    'success' => false,
                    'message' => 'Dados inválidos'
                ]);
    }

    /**
     * Test admin cannot create plan with duplicate name
     */
    public function test_admin_cannot_create_plan_with_duplicate_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $existingPlan = Plan::factory()->create(['name' => 'Unique Plan']);
        
        Sanctum::actingAs($admin);

        $planData = [
            'name' => 'Unique Plan', // Duplicate name
            'description' => 'Another plan',
            'price' => 50,
            'status' => 'active',
            'commission_level_1' => 10,
            'commission_level_2' => 7,
            'commission_level_3' => 5,
            'start_date' => '2025-01-01'
        ];

        $response = $this->postJson('/api/v1/administrator/plans', $planData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test admin can update existing plan
     */
    public function test_admin_can_update_existing_plan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $plan = Plan::factory()->create([
            'name' => 'Original Plan',
            'price' => 100
        ]);
        
        Sanctum::actingAs($admin);

        $updateData = [
            'name' => 'Updated Plan',
            'price' => 150,
            'description' => 'Updated description'
        ];

        $response = $this->putJson("/api/v1/administrator/plans/{$plan->uuid}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Plano atualizado com sucesso'
                ]);

        $this->assertDatabaseHas('plans', [
            'uuid' => $plan->uuid,
            'name' => 'Updated Plan',
            'price' => 150
        ]);
    }

    /**
     * Test admin can delete plan
     */
    public function test_admin_can_delete_plan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $plan = Plan::factory()->create();
        
        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/v1/administrator/plans/{$plan->uuid}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Plano deletado com sucesso'
                ]);

        $this->assertSoftDeleted('plans', [
            'uuid' => $plan->uuid
        ]);
    }

    /**
     * Test admin can toggle plan status
     */
    public function test_admin_can_toggle_plan_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Sanctum::actingAs($admin);

        // Test activating inactive plan
        $inactivePlan = Plan::factory()->create(['status' => 'inactive']);
        $response = $this->postJson("/api/v1/administrator/plans/{$inactivePlan->uuid}/toggle-status");
        
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Plano ativado com sucesso',
                    'data' => [
                        'new_status' => 'active'
                    ]
                ]);

        // Test deactivating active plan
        $activePlan = Plan::factory()->create(['status' => 'active']);
        $response = $this->postJson("/api/v1/administrator/plans/{$activePlan->uuid}/toggle-status");
        
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Plano desativado com sucesso',
                    'data' => [
                        'new_status' => 'inactive'
                    ]
                ]);
    }

    /**
     * Test admin can get plan statistics
     */
    public function test_admin_can_get_plan_statistics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Clear existing plans and create controlled test data
        Plan::query()->delete();
        
        Plan::factory(3)->create(['status' => 'active', 'is_promotional' => false, 'price' => 100]);
        Plan::factory(2)->create(['status' => 'inactive', 'is_promotional' => false, 'price' => 200]);
        Plan::factory(1)->create(['is_promotional' => true, 'status' => 'active', 'price' => 50]);
        
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/administrator/plans/statistics/overview');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'statistics' => [
                            'total_plans',
                            'active_plans',
                            'inactive_plans',
                            'promotional_plans',
                            'average_price',
                            'min_price',
                            'max_price'
                        ]
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Estatísticas dos planos'
                ]);

        $stats = $response->json('data.statistics');
        $this->assertEquals(6, $stats['total_plans']);
        $this->assertEquals(4, $stats['active_plans']); // 3 active + 1 promotional active
        $this->assertEquals(2, $stats['inactive_plans']);
        $this->assertEquals(1, $stats['promotional_plans']);
        $this->assertEquals(50, $stats['min_price']);
        $this->assertEquals(200, $stats['max_price']);
    }

    /**
     * Test plan validation rules for required fields
     */
    public function test_plan_validation_rules_for_required_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/administrator/plans', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'name',
                    'description',
                    'price',
                    'status',
                    'commission_level_1',
                    'commission_level_2',
                    'commission_level_3',
                    'start_date'
                ]);
    }

    /**
     * Test plan validation for numeric and range constraints
     */
    public function test_plan_validation_for_numeric_constraints(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Sanctum::actingAs($admin);

        $invalidData = [
            'name' => 'Test Plan',
            'description' => 'Test description',
            'price' => -10,              // Should be >= 0
            'commission_level_1' => 150, // Should be <= 100
            'commission_level_2' => -5,  // Should be >= 0
            'commission_level_3' => 200, // Should be <= 100
            'status' => 'active',
            'start_date' => '2025-01-01'
        ];

        $response = $this->postJson('/api/v1/administrator/plans', $invalidData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'price',
                    'commission_level_1',
                    'commission_level_2',
                    'commission_level_3'
                ]);
    }

    /**
     * Test plan validation for date constraints
     */
    public function test_plan_validation_for_date_constraints(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Sanctum::actingAs($admin);

        $planData = [
            'name' => 'Test Plan',
            'description' => 'Test description',
            'price' => 100,
            'status' => 'active',
            'commission_level_1' => 10,
            'commission_level_2' => 7,
            'commission_level_3' => 5,
            'start_date' => '2025-12-31',
            'end_date' => '2025-01-01' // End date before start date
        ];

        $response = $this->postJson('/api/v1/administrator/plans', $planData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['end_date']);
    }

    /**
     * Test promotional plan functionality
     */
    public function test_promotional_plan_functionality(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Sanctum::actingAs($admin);

        $promotionalData = [
            'name' => 'Black Friday Special',
            'description' => 'Limited time promotional offer',
            'price' => 49.99,
            'status' => 'active',
            'commission_level_1' => 5,
            'commission_level_2' => 3,
            'commission_level_3' => 2,
            'is_promotional' => true,
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-31'
        ];

        $response = $this->postJson('/api/v1/administrator/plans', $promotionalData);

        $response->assertStatus(201);
        
        $plan = $response->json('data.plan');
        $this->assertTrue($plan['is_promotional']);
        $this->assertEquals('Black Friday Special', $plan['name']);
    }

    /**
     * Test admin handles non-existent plan UUIDs
     */
    public function test_admin_handles_non_existent_plan_uuids(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $fakeUuid = 'fake-uuid-that-does-not-exist';
        
        Sanctum::actingAs($admin);

        // Test show
        $response = $this->getJson("/api/v1/administrator/plans/{$fakeUuid}");
        $response->assertStatus(404)
                ->assertJson(['success' => false, 'message' => 'Plano não encontrado']);

        // Test update
        $response = $this->putJson("/api/v1/administrator/plans/{$fakeUuid}", ['name' => 'Updated']);
        $response->assertStatus(404)
                ->assertJson(['success' => false, 'message' => 'Plano não encontrado']);

        // Test delete
        $response = $this->deleteJson("/api/v1/administrator/plans/{$fakeUuid}");
        $response->assertStatus(404)
                ->assertJson(['success' => false, 'message' => 'Plano não encontrado']);

        // Test toggle status
        $response = $this->postJson("/api/v1/administrator/plans/{$fakeUuid}/toggle-status");
        $response->assertStatus(404)
                ->assertJson(['success' => false, 'message' => 'Plano não encontrado']);
    }

    /**
     * Test non-admin cannot access plan endpoints
     */
    public function test_non_admin_cannot_access_plan_endpoints(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $plan = Plan::factory()->create();
        
        Sanctum::actingAs($customer);

        // Test all endpoints
        $endpoints = [
            ['method' => 'get', 'url' => '/api/v1/administrator/plans'],
            ['method' => 'get', 'url' => "/api/v1/administrator/plans/{$plan->uuid}"],
            ['method' => 'post', 'url' => '/api/v1/administrator/plans', 'data' => ['name' => 'Test']],
            ['method' => 'put', 'url' => "/api/v1/administrator/plans/{$plan->uuid}", 'data' => ['name' => 'Updated']],
            ['method' => 'delete', 'url' => "/api/v1/administrator/plans/{$plan->uuid}"],
            ['method' => 'post', 'url' => "/api/v1/administrator/plans/{$plan->uuid}/toggle-status"],
            ['method' => 'get', 'url' => '/api/v1/administrator/plans/statistics/overview']
        ];

        foreach ($endpoints as $endpoint) {
            $method = $endpoint['method'] . 'Json';
            $data = $endpoint['data'] ?? [];
            
            $response = $this->$method($endpoint['url'], $data);
            $response->assertStatus(403);
        }
    }

    /**
     * Test unauthenticated user cannot access plan endpoints
     */
    public function test_unauthenticated_user_cannot_access_plan_endpoints(): void
    {
        $plan = Plan::factory()->create();

        $endpoints = [
            ['method' => 'get', 'url' => '/api/v1/administrator/plans'],
            ['method' => 'get', 'url' => "/api/v1/administrator/plans/{$plan->uuid}"],
            ['method' => 'post', 'url' => '/api/v1/administrator/plans', 'data' => ['name' => 'Test']],
            ['method' => 'put', 'url' => "/api/v1/administrator/plans/{$plan->uuid}", 'data' => ['name' => 'Updated']],
            ['method' => 'delete', 'url' => "/api/v1/administrator/plans/{$plan->uuid}"],
            ['method' => 'post', 'url' => "/api/v1/administrator/plans/{$plan->uuid}/toggle-status"],
            ['method' => 'get', 'url' => '/api/v1/administrator/plans/statistics/overview']
        ];

        foreach ($endpoints as $endpoint) {
            $method = $endpoint['method'] . 'Json';
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
        Plan::factory(25)->create(); // Create more than default per_page
        
        Sanctum::actingAs($admin);

        // Test default pagination
        $response = $this->getJson('/api/v1/administrator/plans');
        $response->assertStatus(200);
        
        $pagination = $response->json('data.pagination');
        $this->assertEquals(1, $pagination['current_page']);
        $this->assertEquals(15, $pagination['per_page']); // Default per page
        $this->assertEquals(25, $pagination['total']);
        $this->assertEquals(2, $pagination['last_page']);

        // Test custom per_page
        $response = $this->getJson('/api/v1/administrator/plans?per_page=10');
        $response->assertStatus(200);
        
        $pagination = $response->json('data.pagination');
        $this->assertEquals(10, $pagination['per_page']);
        $this->assertEquals(3, $pagination['last_page']);

        // Test specific page
        $response = $this->getJson('/api/v1/administrator/plans?page=2&per_page=10');
        $response->assertStatus(200);
        
        $pagination = $response->json('data.pagination');
        $this->assertEquals(2, $pagination['current_page']);
    }

    /**
     * Test complex filtering combinations
     */
    public function test_complex_filtering_combinations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create specific test data
        Plan::factory()->create([
            'name' => 'Premium Gold Plan',
            'status' => 'active',
            'is_promotional' => true,
            'price' => 150
        ]);
        
        Plan::factory()->create([
            'name' => 'Basic Silver Plan', 
            'status' => 'active',
            'is_promotional' => false,
            'price' => 80
        ]);
        
        Plan::factory()->create([
            'name' => 'Premium Bronze Plan',
            'status' => 'inactive',
            'is_promotional' => true,
            'price' => 120
        ]);
        
        Sanctum::actingAs($admin);

        // Test multiple filters combined
        $response = $this->getJson('/api/v1/administrator/plans?status=active&promotional=true&min_price=100&search=Premium');

        $response->assertStatus(200);
        $plans = $response->json('data.plans');
        
        // Should only return the Premium Gold Plan
        $this->assertCount(1, $plans);
        $this->assertEquals('Premium Gold Plan', $plans[0]['name']);
        $this->assertEquals('active', $plans[0]['status']);
        $this->assertTrue($plans[0]['is_promotional']);
        $this->assertGreaterThanOrEqual(100, $plans[0]['price']);
    }
}