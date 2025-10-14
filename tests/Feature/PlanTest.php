<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PlanTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_customer_can_list_active_plans()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Criar planos com diferentes status
        Plan::factory()->create(['status' => 'active', 'name' => 'Plano Ativo']);
        Plan::factory()->create(['status' => 'inactive', 'name' => 'Plano Inativo']);
        Plan::factory()->create(['status' => 'archived', 'name' => 'Plano Arquivado']);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->getJson('/api/v1/customer/plans');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'plans',
                    'total'
                ]
            ]);

        // Verificar que apenas planos ativos são retornados
        $plans = $response->json('data.plans');
        $this->assertCount(1, $plans);
        $this->assertEquals('Plano Ativo', $plans[0]['name']);
    }

    public function test_admin_can_create_plan()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test-token')->plainTextToken;

        $planData = [
            'name' => 'Novo Plano',
            'description' => 'Descrição do plano',
            'price' => 99.90,
            'grant_tickets' => 10,
            'status' => 'active',
            'commission_level_1' => 10.00,
            'commission_level_2' => 5.00,
            'commission_level_3' => 2.00,
            'is_promotional' => false,
            'overlap' => 5,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonths(6)->format('Y-m-d'),
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson('/api/v1/administrator/plans', $planData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['plan']
            ]);

        $this->assertDatabaseHas('plans', [
            'name' => 'Novo Plano',
            'price' => 99.90,
            'status' => 'active'
        ]);
    }

    public function test_admin_can_update_plan()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test-token')->plainTextToken;
        
        $plan = Plan::factory()->create(['name' => 'Plano Original']);

        $updateData = [
            'name' => 'Plano Atualizado',
            'price' => 149.90,
            'status' => 'inactive'
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->putJson("/api/v1/administrator/plans/{$plan->uuid}", $updateData);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('plans', [
            'uuid' => $plan->uuid,
            'name' => 'Plano Atualizado',
            'price' => 149.90,
            'status' => 'inactive'
        ]);
    }

    public function test_regular_user_cannot_access_admin_plan_endpoints()
    {
        $user = User::factory()->create(['role' => 'user']);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->getJson('/api/v1/administrator/plans');

        $response->assertStatus(403);
    }

    public function test_plan_validation_enforces_status_enum()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test-token')->plainTextToken;

        $planData = [
            'name' => 'Plano Teste',
            'description' => 'Teste',
            'price' => 99.90,
            'grant_tickets' => 10,
            'status' => 'suspended', // Status inválido
            'commission_level_1' => 10.00,
            'commission_level_2' => 5.00,
            'commission_level_3' => 2.00,
            'overlap' => 5,
            'start_date' => now()->format('Y-m-d'),
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson('/api/v1/administrator/plans', $planData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }
}