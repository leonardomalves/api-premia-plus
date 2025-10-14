<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdministratorSystemTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test admin can get system statistics
     */
    public function test_admin_can_get_system_statistics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sponsor = User::factory()->create();
        
        // Criar usuários com diferentes configurações
        User::factory(5)->create(['role' => 'user', 'status' => 'active']);
        User::factory(3)->create(['role' => 'user', 'status' => 'inactive']);
        User::factory(2)->create(['role' => 'moderator', 'status' => 'active']);
        User::factory(1)->create(['role' => 'user', 'status' => 'suspended']);
        User::factory(3)->create(['sponsor_id' => $sponsor->id]); // Com patrocinador
        User::factory(2)->create(['sponsor_id' => null]); // Sem patrocinador
        
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/administrator/statistics');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'system_statistics' => [
                        'total_users',
                        'active_users',
                        'inactive_users',
                        'suspended_users',
                        'users_by_role',
                        'users_by_status',
                        'users_with_sponsors',
                        'users_without_sponsors'
                    ]
                ]);

        $stats = $response->json('system_statistics');
        $this->assertEquals(18, $stats['total_users']); // Todos os usuários + admin + sponsor
        $this->assertEquals(14, $stats['active_users']); // Default é active
        $this->assertEquals(3, $stats['inactive_users']);
        $this->assertEquals(1, $stats['suspended_users']);
        $this->assertEquals(3, $stats['users_with_sponsors']);
        $this->assertArrayHasKey('user', $stats['users_by_role']);
        $this->assertArrayHasKey('admin', $stats['users_by_role']);
        $this->assertArrayHasKey('moderator', $stats['users_by_role']);
    }

    /**
     * Test admin can get dashboard overview
     */
    public function test_admin_can_get_dashboard_overview(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Criar usuários para dashboard
        $topSponsor = User::factory()->create(['name' => 'Top Sponsor']);
        User::factory(8)->create(['sponsor_id' => $topSponsor->id]); // Maior rede
        
        $mediumSponsor = User::factory()->create(['name' => 'Medium Sponsor']);
        User::factory(3)->create(['sponsor_id' => $mediumSponsor->id]);
        
        // Usuários adicionais para estatísticas
        User::factory(2)->create(['status' => 'inactive']);
        User::factory(1)->create(['status' => 'suspended']);
        
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/administrator/dashboard');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'summary' => [
                        'total_users',
                        'active_users',
                        'inactive_users',
                        'suspended_users',
                        'new_users_last_30_days'
                    ],
                    'top_sponsors' => [
                        '*' => [
                            'uuid',
                            'name',
                            'email',
                            'sponsored_count'
                        ]
                    ],
                    'recent_users' => [
                        '*' => [
                            'uuid',
                            'name',
                            'email',
                            'role',
                            'status',
                            'created_at'
                        ]
                    ]
                ]);

        $dashboardData = $response->json();
        
        // Verificar summary
        $this->assertEquals(17, $dashboardData['summary']['total_users']);
        $this->assertEquals(14, $dashboardData['summary']['active_users']);
        $this->assertEquals(2, $dashboardData['summary']['inactive_users']);
        $this->assertEquals(1, $dashboardData['summary']['suspended_users']);
        
        // Verificar top sponsors
        $this->assertCount(5, $dashboardData['top_sponsors']); // Máximo 5
        $this->assertEquals('Top Sponsor', $dashboardData['top_sponsors'][0]['name']);
        $this->assertEquals(8, $dashboardData['top_sponsors'][0]['sponsored_count']);
        $this->assertEquals('Medium Sponsor', $dashboardData['top_sponsors'][1]['name']);
        $this->assertEquals(3, $dashboardData['top_sponsors'][1]['sponsored_count']);
        
        // Verificar recent users
        $this->assertCount(5, $dashboardData['recent_users']); // Máximo 5
    }

    /**
     * Test admin can list all plans with pagination
     */
    public function test_admin_can_list_all_plans(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Criar planos variados
        Plan::factory(5)->create(['status' => 'active']);
        Plan::factory(3)->create(['status' => 'inactive']);
        Plan::factory(2)->create(['status' => 'archived']);
        Plan::factory(2)->create(['is_promotional' => true, 'status' => 'active']);
        
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/administrator/plans');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'plans' => [
                            '*' => [
                                'id',
                                'uuid',
                                'name',
                                'description',
                                'price',
                                'grant_tickets',
                                'status',
                                'commission_level_1',
                                'commission_level_2',
                                'commission_level_3',
                                'is_promotional',
                                'overlap',
                                'start_date',
                                'end_date',
                                'created_at',
                                'updated_at'
                            ]
                        ],
                        'pagination' => [
                            'current_page',
                            'per_page',
                            'total',
                            'last_page',
                            'from',
                            'to'
                        ],
                        'filters'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Planos listados com sucesso'
                ]);

        $plansData = $response->json('data');
        $this->assertEquals(12, $plansData['pagination']['total']); // Total de planos criados
        $this->assertEquals(12, count($plansData['plans'])); // Todos cabem na primeira página
    }

    /**
     * Test admin can filter plans by status
     */
    public function test_admin_can_filter_plans_by_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Plan::factory(4)->create(['status' => 'active']);
        Plan::factory(2)->create(['status' => 'inactive']);
        Plan::factory(1)->create(['status' => 'archived']);
        
        Sanctum::actingAs($admin);

        // Filtrar por status active
        $response = $this->getJson('/api/v1/administrator/plans?status=active');
        $response->assertStatus(200);
        $plans = $response->json('data.plans');
        $this->assertCount(4, $plans);
        foreach ($plans as $plan) {
            $this->assertEquals('active', $plan['status']);
        }

        // Filtrar por status inactive
        $response = $this->getJson('/api/v1/administrator/plans?status=inactive');
        $response->assertStatus(200);
        $plans = $response->json('data.plans');
        $this->assertCount(2, $plans);
        foreach ($plans as $plan) {
            $this->assertEquals('inactive', $plan['status']);
        }
    }

    /**
     * Test admin can search plans by name or description
     */
    public function test_admin_can_search_plans(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Plan::factory()->create([
            'name' => 'Premium Gold Plan',
            'description' => 'The best premium subscription'
        ]);
        Plan::factory()->create([
            'name' => 'Basic Plan',
            'description' => 'Entry level subscription'
        ]);
        Plan::factory()->create([
            'name' => 'Enterprise Solution',
            'description' => 'Full business package'
        ]);
        
        Sanctum::actingAs($admin);

        // Buscar por nome
        $response = $this->getJson('/api/v1/administrator/plans?search=Premium');
        $response->assertStatus(200);
        $plans = $response->json('data.plans');
        $this->assertCount(1, $plans);
        $this->assertStringContainsString('Premium', $plans[0]['name']);

        // Buscar por descrição
        $response = $this->getJson('/api/v1/administrator/plans?search=business');
        $response->assertStatus(200);
        $plans = $response->json('data.plans');
        $this->assertCount(1, $plans);
        $this->assertStringContainsString('business', $plans[0]['description']);
    }

    /**
     * Test admin can view specific plan
     */
    public function test_admin_can_view_specific_plan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $plan = Plan::factory()->create([
            'name' => 'Test Plan',
            'price' => 299.99,
            'status' => 'active'
        ]);
        
        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/v1/administrator/plans/{$plan->uuid}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Plano encontrado com sucesso',
                    'data' => [
                        'plan' => [
                            'uuid' => $plan->uuid,
                            'name' => 'Test Plan',
                            'price' => '299.99',
                            'status' => 'active'
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
            'name' => 'New Premium Plan',
            'description' => 'A brand new premium subscription plan',
            'price' => 199.99,
            'grant_tickets' => 15,
            'status' => 'active',
            'commission_level_1' => 20.00,
            'commission_level_2' => 15.00,
            'commission_level_3' => 10.00,
            'is_promotional' => true,
            'overlap' => 5,
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
                            'id',
                            'uuid',
                            'name',
                            'description',
                            'price',
                            'grant_tickets',
                            'status',
                            'commission_level_1',
                            'commission_level_2',
                            'commission_level_3',
                            'is_promotional',
                            'overlap',
                            'start_date',
                            'end_date',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Plano criado com sucesso',
                    'data' => [
                        'plan' => [
                            'name' => 'New Premium Plan',
                            'description' => 'A brand new premium subscription plan',
                            'price' => '199.99',
                            'grant_tickets' => 15,
                            'status' => 'active',
                            'is_promotional' => true
                        ]
                    ]
                ]);

        $this->assertDatabaseHas('plans', [
            'name' => 'New Premium Plan',
            'price' => 199.99,
            'status' => 'active'
        ]);
    }

    /**
     * Test admin cannot create plan with duplicate name
     */
    public function test_admin_cannot_create_plan_with_duplicate_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $existingPlan = Plan::factory()->create(['name' => 'Existing Plan']);
        
        Sanctum::actingAs($admin);

        $planData = [
            'name' => 'Existing Plan', // Nome duplicado
            'description' => 'Another plan with same name',
            'price' => 99.99,
            'grant_tickets' => 10,
            'status' => 'active',
            'commission_level_1' => 15.00,
            'commission_level_2' => 10.00,
            'commission_level_3' => 5.00,
            'overlap' => 3,
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
            'name' => 'Old Plan Name',
            'price' => 99.99,
            'status' => 'inactive'
        ]);
        
        Sanctum::actingAs($admin);

        $updateData = [
            'name' => 'Updated Plan Name',
            'price' => 149.99,
            'status' => 'active',
            'description' => 'Updated description'
        ];

        $response = $this->putJson("/api/v1/administrator/plans/{$plan->uuid}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Plano atualizado com sucesso',
                    'data' => [
                        'plan' => [
                            'uuid' => $plan->uuid,
                            'name' => 'Updated Plan Name',
                            'price' => '149.99',
                            'status' => 'active',
                            'description' => 'Updated description'
                        ]
                    ]
                ]);

        $this->assertDatabaseHas('plans', [
            'uuid' => $plan->uuid,
            'name' => 'Updated Plan Name',
            'price' => 149.99,
            'status' => 'active'
        ]);
    }

    /**
     * Test admin can delete plan
     */
    public function test_admin_can_delete_plan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $plan = Plan::factory()->create(['name' => 'Plan to Delete']);
        
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
        $activePlan = Plan::factory()->create(['status' => 'active']);
        $inactivePlan = Plan::factory()->create(['status' => 'inactive']);
        
        Sanctum::actingAs($admin);

        // Desativar plano ativo
        $response = $this->postJson("/api/v1/administrator/plans/{$activePlan->uuid}/toggle-status");
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Plano desativado com sucesso',
                    'data' => [
                        'new_status' => 'inactive',
                        'plan' => [
                            'uuid' => $activePlan->uuid,
                            'status' => 'inactive'
                        ]
                    ]
                ]);

        // Ativar plano inativo
        $response = $this->postJson("/api/v1/administrator/plans/{$inactivePlan->uuid}/toggle-status");
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Plano ativado com sucesso',
                    'data' => [
                        'new_status' => 'active',
                        'plan' => [
                            'uuid' => $inactivePlan->uuid,
                            'status' => 'active'
                        ]
                    ]
                ]);
    }

    /**
     * Test admin can get plan statistics
     */
    public function test_admin_can_get_plan_statistics(): void
    {
        // Limpar qualquer dado existente para ter controle total dos counts
        Plan::query()->delete();
        
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Criar planos para estatísticas
        Plan::factory(5)->create(['status' => 'active', 'is_promotional' => false, 'price' => 100, 'grant_tickets' => 10]);
        Plan::factory(3)->create(['status' => 'inactive', 'is_promotional' => false, 'price' => 200, 'grant_tickets' => 20]);
        Plan::factory(2)->create(['is_promotional' => true, 'status' => 'active', 'price' => 50, 'grant_tickets' => 5]);
        
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
                            'max_price',
                            'total_tickets'
                        ]
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Estatísticas dos planos'
                ]);

        $stats = $response->json('data.statistics');
        $this->assertEquals(10, $stats['total_plans']);
        $this->assertEquals(7, $stats['active_plans']); // 5 active + 2 promotional active
        $this->assertEquals(3, $stats['inactive_plans']); // 3 inactive
        $this->assertEquals(2, $stats['promotional_plans']);
        $this->assertEquals(50, $stats['min_price']);
        $this->assertEquals(200, $stats['max_price']);
        $this->assertEquals(120, $stats['total_tickets']); // (5*10) + (3*20) + (2*5) = 50 + 60 + 10 = 120
    }

    /**
     * Test admin endpoints handle non-existent plan UUIDs
     */
    public function test_admin_plan_endpoints_handle_non_existent_uuids(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $fakeUuid = $this->faker->uuid();
        
        Sanctum::actingAs($admin);

        $endpoints = [
            ['GET', "/api/v1/administrator/plans/{$fakeUuid}"],
            ['PUT', "/api/v1/administrator/plans/{$fakeUuid}"],
            ['DELETE', "/api/v1/administrator/plans/{$fakeUuid}"],
            ['POST', "/api/v1/administrator/plans/{$fakeUuid}/toggle-status"]
        ];

        foreach ($endpoints as [$method, $endpoint]) {
            $response = $this->json($method, $endpoint, []);
            $response->assertStatus(404)
                    ->assertJson([
                        'success' => false,
                        'message' => 'Plano não encontrado'
                    ]);
        }
    }

    /**
     * Test non-admin cannot access administrator system endpoints
     */
    public function test_non_admin_cannot_access_administrator_system_endpoints(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $plan = Plan::factory()->create();
        
        Sanctum::actingAs($user);

        $adminEndpoints = [
            ['GET', '/api/v1/administrator/statistics'],
            ['GET', '/api/v1/administrator/dashboard'],
            ['GET', '/api/v1/administrator/plans'],
            ['GET', "/api/v1/administrator/plans/{$plan->uuid}"],
            ['POST', '/api/v1/administrator/plans'],
            ['PUT', "/api/v1/administrator/plans/{$plan->uuid}"],
            ['DELETE', "/api/v1/administrator/plans/{$plan->uuid}"],
            ['POST', "/api/v1/administrator/plans/{$plan->uuid}/toggle-status"],
            ['GET', '/api/v1/administrator/plans/statistics/overview']
        ];

        foreach ($adminEndpoints as [$method, $endpoint]) {
            $response = $this->json($method, $endpoint, []);
            $response->assertStatus(403)
                    ->assertJson([
                        'message' => 'Acesso negado. Apenas administradores podem acessar esta funcionalidade.',
                        'error' => 'Forbidden'
                    ]);
        }
    }

    /**
     * Test unauthenticated user cannot access administrator system endpoints
     */
    public function test_unauthenticated_user_cannot_access_administrator_system_endpoints(): void
    {
        $plan = Plan::factory()->create();

        $adminEndpoints = [
            ['GET', '/api/v1/administrator/statistics'],
            ['GET', '/api/v1/administrator/dashboard'],
            ['GET', '/api/v1/administrator/plans'],
            ['GET', "/api/v1/administrator/plans/{$plan->uuid}"],
            ['POST', '/api/v1/administrator/plans'],
            ['PUT', "/api/v1/administrator/plans/{$plan->uuid}"],
            ['DELETE', "/api/v1/administrator/plans/{$plan->uuid}"],
            ['POST', "/api/v1/administrator/plans/{$plan->uuid}/toggle-status"],
            ['GET', '/api/v1/administrator/plans/statistics/overview']
        ];

        foreach ($adminEndpoints as [$method, $endpoint]) {
            $response = $this->json($method, $endpoint, []);
            $response->assertStatus(401);
        }
    }

    /**
     * Test admin can sort and paginate plans
     */
    public function test_admin_can_sort_and_paginate_plans(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Criar planos com preços diferentes
        Plan::factory()->create(['name' => 'Plan A', 'price' => 300]);
        Plan::factory()->create(['name' => 'Plan B', 'price' => 100]);
        Plan::factory()->create(['name' => 'Plan C', 'price' => 200]);
        
        Sanctum::actingAs($admin);

        // Testar ordenação por preço ascendente
        $response = $this->getJson('/api/v1/administrator/plans?sort_by=price&sort_order=asc');
        $response->assertStatus(200);
        
        $plans = $response->json('data.plans');
        $prices = array_column($plans, 'price');
        $this->assertEquals([100, 200, 300], array_map('intval', $prices));

        // Testar ordenação por nome
        $response = $this->getJson('/api/v1/administrator/plans?sort_by=name&sort_order=asc');
        $response->assertStatus(200);
        
        $plans = $response->json('data.plans');
        $names = array_column($plans, 'name');
        $this->assertEquals(['Plan A', 'Plan B', 'Plan C'], $names);

        // Testar paginação
        $response = $this->getJson('/api/v1/administrator/plans?per_page=2');
        $response->assertStatus(200);
        
        $pagination = $response->json('data.pagination');
        $this->assertEquals(2, $pagination['per_page']);
        $this->assertEquals(3, $pagination['total']);
        $this->assertEquals(2, $pagination['last_page']);
    }

    /**
     * Test plan validation rules
     */
    public function test_plan_validation_rules(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        Sanctum::actingAs($admin);

        // Testar dados obrigatórios em branco
        $response = $this->postJson('/api/v1/administrator/plans', []);
        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'name',
                    'description',
                    'price',
                    'grant_tickets',
                    'status',
                    'commission_level_1',
                    'commission_level_2',
                    'commission_level_3',
                    'overlap',
                    'start_date'
                ]);

        // Testar valores inválidos
        $invalidData = [
            'name' => '', // Vazio
            'description' => str_repeat('a', 1001), // Muito longo
            'price' => -1, // Negativo
            'grant_tickets' => -1, // Negativo
            'status' => 'invalid_status', // Status inválido
            'commission_level_1' => 101, // Acima de 100
            'commission_level_2' => -1, // Negativo
            'commission_level_3' => 'invalid', // Não numérico
            'overlap' => -1, // Negativo
            'start_date' => 'invalid_date', // Data inválida
            'end_date' => '2024-01-01' // Anterior à start_date
        ];

        $response = $this->postJson('/api/v1/administrator/plans', array_merge($invalidData, [
            'start_date' => '2025-01-01'
        ]));
        $response->assertStatus(422);
    }
}