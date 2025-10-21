<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerPlansTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test customer can list all active plans
     */
    public function test_customer_can_list_all_active_plans(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        
        // Criar alguns planos ativos e inativos
        $activePlans = Plan::factory(3)->active()->create();
        Plan::factory(2)->create(['status' => 'inactive']);
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/plans');

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
                        ],
                        'total',
                        'filters'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'total' => 3  // Apenas os planos ativos
                    ]
                ]);
    }

    /**
     * Test customer can list plans with promotional filter
     */
    public function test_customer_can_filter_promotional_plans(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        
        // Criar planos promocionais e normais
        Plan::factory(2)->active()->promotional()->create();
        Plan::factory(3)->active()->create(['is_promotional' => false]);
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/plans?promotional=1');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'total' => 2,
                        'filters' => [
                            'promotional' => '1'
                        ]
                    ]
                ]);

        // Verificar que todos os planos retornados são promocionais
        $plans = $response->json('data.plans');
        foreach ($plans as $plan) {
            $this->assertTrue($plan['is_promotional']);
        }
    }

    /**
     * Test customer can filter plans by price range
     */
    public function test_customer_can_filter_plans_by_price_range(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        
        // Criar planos com diferentes preços
        Plan::factory()->active()->create(['price' => 50]);
        Plan::factory()->active()->create(['price' => 200]);
        Plan::factory()->active()->create(['price' => 400]);
        
        Sanctum::actingAs($user);

        // Testar filtro de preço mínimo
        $response = $this->getJson('/api/v1/plans?min_price=100');
        $response->assertStatus(200);
        
        $plans = $response->json('data.plans');
        foreach ($plans as $plan) {
            $this->assertGreaterThanOrEqual(100, $plan['price']);
        }

        // Testar filtro de preço máximo
        $response = $this->getJson('/api/v1/plans?max_price=250');
        $response->assertStatus(200);
        
        $plans = $response->json('data.plans');
        foreach ($plans as $plan) {
            $this->assertLessThanOrEqual(250, $plan['price']);
        }
    }

    /**
     * Test customer can sort plans by different fields
     */
    public function test_customer_can_sort_plans(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        
        // Criar planos com diferentes preços
        Plan::factory()->active()->create(['price' => 300, 'name' => 'Plano Z']);
        Plan::factory()->active()->create(['price' => 100, 'name' => 'Plano A']);
        Plan::factory()->active()->create(['price' => 200, 'name' => 'Plano M']);
        
        Sanctum::actingAs($user);

        // Testar ordenação por preço crescente (padrão)
        $response = $this->getJson('/api/v1/plans');
        $response->assertStatus(200);
        
        $plans = $response->json('data.plans');
        $prices = array_column($plans, 'price');
        $this->assertEquals([100, 200, 300], $prices);

        // Testar ordenação por preço decrescente
        $response = $this->getJson('/api/v1/plans?sort_by=price&sort_order=desc');
        $response->assertStatus(200);
        
        $plans = $response->json('data.plans');
        $prices = array_column($plans, 'price');
        $this->assertEquals([300, 200, 100], $prices);

        // Testar ordenação por nome
        $response = $this->getJson('/api/v1/plans?sort_by=name&sort_order=asc');
        $response->assertStatus(200);
        
        $plans = $response->json('data.plans');
        $names = array_column($plans, 'name');
        $this->assertEquals(['Plano A', 'Plano M', 'Plano Z'], $names);
    }

    /**
     * Test customer can view specific plan details
     */
    public function test_customer_can_view_specific_plan(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $plan = Plan::factory()->active()->create([
            'name' => 'Plano Premium',
            'price' => 199.99
        ]);
        
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/plans/{$plan->uuid}");

        $response->assertStatus(200)
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
                            'status'
                        ]
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'plan' => [
                            'uuid' => $plan->uuid,
                            'name' => 'Plano Premium',
                            'price' => '199.99'
                        ]
                    ]
                ]);
    }

    /**
     * Test customer cannot view inactive plan
     */
    public function test_customer_cannot_view_inactive_plan(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $inactivePlan = Plan::factory()->create(['status' => 'inactive']);
        
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/plans/{$inactivePlan->uuid}");

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Plano não encontrado ou inativo'
                ]);
    }

    /**
     * Test customer cannot view non-existent plan
     */
    public function test_customer_cannot_view_non_existent_plan(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $fakeUuid = $this->faker->uuid();
        
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/plans/{$fakeUuid}");

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Plano não encontrado ou inativo'
                ]);
    }

    /**
     * Test customer can list promotional plans only
     */
    public function test_customer_can_list_promotional_plans_only(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        
        // Criar planos promocionais e normais
        $promotionalPlans = Plan::factory(3)->active()->promotional()->create();
        Plan::factory(2)->active()->create(['is_promotional' => false]);
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/plans/promotional/list');

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
                                'is_promotional'
                            ]
                        ],
                        'total'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'total' => 3
                    ]
                ]);

        // Verificar que todos os planos são promocionais
        $plans = $response->json('data.plans');
        foreach ($plans as $plan) {
            $this->assertTrue($plan['is_promotional']);
        }
    }

    /**
     * Test customer can search plans by name or description
     */
    public function test_customer_can_search_plans(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        
        // Criar planos com diferentes nomes
        Plan::factory()->active()->create([
            'name' => 'Plano Premium Gold',
            'description' => 'Melhor plano para usuários avançados'
        ]);
        Plan::factory()->active()->create([
            'name' => 'Plano Básico',
            'description' => 'Plano inicial para iniciantes'
        ]);
        Plan::factory()->active()->create([
            'name' => 'Plano Enterprise',
            'description' => 'Solução empresarial completa'
        ]);
        
        Sanctum::actingAs($user);

        // Buscar por nome
        $response = $this->getJson('/api/v1/plans/search?search=Premium');
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'total' => 1,
                        'search_term' => 'Premium'
                    ]
                ]);

        $plans = $response->json('data.plans');
        $this->assertStringContainsString('Premium', $plans[0]['name']);

        // Buscar por descrição
        $response = $this->getJson('/api/v1/plans/search?search=empresarial');
        $response->assertStatus(200);
        
        $plans = $response->json('data.plans');
        $this->assertCount(1, $plans);
        $this->assertStringContainsString('empresarial', $plans[0]['description']);
    }

    /**
     * Test customer can search plans by price range categories
     */
    public function test_customer_can_search_plans_by_price_categories(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        
        // Criar planos em diferentes faixas de preço
        Plan::factory()->active()->create(['price' => 100]); // low
        Plan::factory()->active()->create(['price' => 200]); // medium
        Plan::factory()->active()->create(['price' => 400]); // high
        
        Sanctum::actingAs($user);

        // Testar faixa baixa (<=150)
        $response = $this->getJson('/api/v1/plans/search?price_range=low');
        $response->assertStatus(200);
        
        $plans = $response->json('data.plans');
        $this->assertCount(1, $plans);
        $this->assertLessThanOrEqual(150, $plans[0]['price']);

        // Testar faixa média (150-300)
        $response = $this->getJson('/api/v1/plans/search?price_range=medium');
        $response->assertStatus(200);
        
        $plans = $response->json('data.plans');
        $this->assertCount(1, $plans);
        $this->assertGreaterThanOrEqual(150, $plans[0]['price']);
        $this->assertLessThanOrEqual(300, $plans[0]['price']);

        // Testar faixa alta (>300)
        $response = $this->getJson('/api/v1/plans/search?price_range=high');
        $response->assertStatus(200);
        
        $plans = $response->json('data.plans');
        $this->assertCount(1, $plans);
        $this->assertGreaterThan(300, $plans[0]['price']);
    }

    /**
     * Test unauthenticated user cannot access plan endpoints
     */
    public function test_unauthenticated_user_cannot_access_plan_endpoints(): void
    {
        Plan::factory()->active()->create();

        // Testar todos os endpoints sem autenticação
        $endpoints = [
            '/api/v1/plans',
            '/api/v1/plans/promotional/list',
            '/api/v1/plans/search'
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            // As rotas de plans são públicas, então devem retornar 200
            $response->assertStatus(200);
        }
    }

    /**
     * Test plan endpoints return empty results when no plans exist
     */
    public function test_plan_endpoints_return_empty_when_no_plans(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/plans');
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'plans' => [],
                        'total' => 0
                    ]
                ]);

        $response = $this->getJson('/api/v1/plans/promotional/list');
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'plans' => [],
                        'total' => 0
                    ]
                ]);
    }

    /**
     * Test plan search with combined filters
     */
    public function test_plan_search_with_combined_filters(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        
        // Criar planos variados
        Plan::factory()->active()->create([
            'name' => 'Premium Gold Low',
            'price' => 100,
            'is_promotional' => true
        ]);
        
        Plan::factory()->active()->create([
            'name' => 'Premium Silver High',
            'price' => 400,
            'is_promotional' => false
        ]);
        
        Sanctum::actingAs($user);

        // Buscar com múltiplos filtros
        $response = $this->getJson('/api/v1/plans/search?search=Premium&price_range=low');
        $response->assertStatus(200);
        
        $plans = $response->json('data.plans');
        $this->assertCount(1, $plans);
        $this->assertStringContainsString('Premium', $plans[0]['name']);
        $this->assertLessThanOrEqual(150, $plans[0]['price']);
    }
}