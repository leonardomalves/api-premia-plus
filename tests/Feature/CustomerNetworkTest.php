<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerNetworkTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test customer can view their network with pagination
     */
    public function test_customer_can_view_network_with_pagination(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        // Criar 20 usuários patrocinados para testar paginação
        User::factory(20)->create(['sponsor_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/customer/network');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'network' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'id',
                            'uuid',
                            'name',
                            'email',
                            'username',
                            'role',
                            'status',
                            'sponsor_id',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'first_page_url',
                    'from',
                    'last_page',
                    'last_page_url',
                    'next_page_url',
                    'path',
                    'per_page',
                    'prev_page_url',
                    'to',
                    'total',
                ],
                'total_network',
                'user_info' => [
                    'uuid',
                    'name',
                    'email',
                ],
            ])
            ->assertJson([
                'total_network' => 20,
                'network' => [
                    'per_page' => 15,
                    'total' => 20,
                    'current_page' => 1,
                ],
            ]);

        // Verificar que retornou 15 items na primeira página
        $this->assertCount(15, $response->json('network.data'));
    }

    /**
     * Test customer can navigate network pagination
     */
    public function test_customer_can_navigate_network_pagination(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        // Criar 20 usuários patrocinados
        User::factory(20)->create(['sponsor_id' => $user->id]);

        Sanctum::actingAs($user);

        // Testar página 2
        $response = $this->getJson('/api/v1/customer/network?page=2');

        $response->assertStatus(200)
            ->assertJson([
                'network' => [
                    'current_page' => 2,
                    'per_page' => 15,
                    'total' => 20,
                ],
            ]);

        // Verificar que retornou 5 items na segunda página (20 total - 15 primeira página)
        $this->assertCount(5, $response->json('network.data'));
    }

    /**
     * Test customer can view network with different user statuses
     */
    public function test_customer_can_view_network_with_different_statuses(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        // Criar usuários com diferentes status
        $activeUser = User::factory()->create([
            'sponsor_id' => $user->id,
            'status' => 'active',
            'name' => 'Active User',
        ]);
        $inactiveUser = User::factory()->create([
            'sponsor_id' => $user->id,
            'status' => 'inactive',
            'name' => 'Inactive User',
        ]);
        $suspendedUser = User::factory()->create([
            'sponsor_id' => $user->id,
            'status' => 'suspended',
            'name' => 'Suspended User',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/customer/network');

        $response->assertStatus(200);

        $networkData = $response->json('network.data');
        $this->assertCount(3, $networkData);

        // Verificar que todos os status são retornados
        $statuses = collect($networkData)->pluck('status')->toArray();
        $this->assertContains('active', $statuses);
        $this->assertContains('inactive', $statuses);
        $this->assertContains('suspended', $statuses);
    }

    /**
     * Test customer statistics include accurate counts by status
     */
    public function test_customer_statistics_include_status_counts(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        // Criar rede com diferentes status
        User::factory(3)->create(['sponsor_id' => $user->id, 'status' => 'active']);
        User::factory(2)->create(['sponsor_id' => $user->id, 'status' => 'inactive']);
        User::factory(1)->create(['sponsor_id' => $user->id, 'status' => 'suspended']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/customer/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'statistics' => [
                    'total_network',
                    'active_network',
                    'inactive_network',
                    'suspended_network',
                    'account_created_at',
                    'last_login',
                    'user_info' => [
                        'uuid',
                        'name',
                        'email',
                        'role',
                        'status',
                    ],
                ],
            ])
            ->assertJson([
                'statistics' => [
                    'total_network' => 6,
                    'active_network' => 3,
                    'inactive_network' => 2,
                    'suspended_network' => 1,
                    'user_info' => [
                        'role' => 'user',
                    ],
                ],
            ]);
    }

    /**
     * Test admin can view any user's network
     */
    public function test_admin_can_view_any_user_network(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $targetUser = User::factory()->create(['role' => 'user']);

        // Criar rede para o usuário alvo
        User::factory(3)->create(['sponsor_id' => $targetUser->id]);

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/v1/customer/users/{$targetUser->uuid}/network");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'network' => [
                    'data' => [
                        '*' => [
                            'id',
                            'uuid',
                            'name',
                            'email',
                            'sponsor_id',
                        ],
                    ],
                ],
                'total_network',
                'target_user' => [
                    'uuid',
                    'name',
                    'email',
                ],
            ])
            ->assertJson([
                'total_network' => 3,
                'target_user' => [
                    'uuid' => $targetUser->uuid,
                    'name' => $targetUser->name,
                ],
            ]);
    }

    /**
     * Test regular user cannot view other user's network
     */
    public function test_regular_user_cannot_view_other_user_network(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $otherUser = User::factory()->create(['role' => 'user']);

        // Criar rede para o outro usuário
        User::factory(2)->create(['sponsor_id' => $otherUser->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/customer/users/{$otherUser->uuid}/network");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Acesso negado.',
            ]);
    }

    /**
     * Test user can view their own network via user-specific endpoint
     */
    public function test_user_can_view_own_network_via_user_endpoint(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        // Criar rede
        User::factory(2)->create(['sponsor_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/customer/users/{$user->uuid}/network");

        $response->assertStatus(200)
            ->assertJson([
                'total_network' => 2,
                'target_user' => [
                    'uuid' => $user->uuid,
                    'name' => $user->name,
                ],
            ]);
    }

    /**
     * Test admin can view any user's sponsor
     */
    public function test_admin_can_view_any_user_sponsor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sponsor = User::factory()->create(['name' => 'Sponsor User']);
        $targetUser = User::factory()->create([
            'sponsor_id' => $sponsor->id,
            'name' => 'Target User',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/v1/customer/users/{$targetUser->uuid}/sponsor");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'sponsor' => [
                    'uuid',
                    'name',
                    'email',
                    'phone',
                    'created_at',
                ],
                'target_user' => [
                    'uuid',
                    'name',
                    'email',
                ],
            ])
            ->assertJson([
                'sponsor' => [
                    'name' => 'Sponsor User',
                    'uuid' => $sponsor->uuid,
                ],
                'target_user' => [
                    'name' => 'Target User',
                    'uuid' => $targetUser->uuid,
                ],
            ]);
    }

    /**
     * Test regular user cannot view other user's sponsor
     */
    public function test_regular_user_cannot_view_other_user_sponsor(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $sponsor = User::factory()->create();
        $otherUser = User::factory()->create(['sponsor_id' => $sponsor->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/customer/users/{$otherUser->uuid}/sponsor");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Acesso negado.',
            ]);
    }

    /**
     * Test user without sponsor via user-specific endpoint
     */
    public function test_user_without_sponsor_via_user_endpoint(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $userWithoutSponsor = User::factory()->create(['sponsor_id' => null]);

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/v1/customer/users/{$userWithoutSponsor->uuid}/sponsor");

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Usuário não possui patrocinador',
            ]);
    }

    /**
     * Test admin can view any user's statistics
     */
    public function test_admin_can_view_any_user_statistics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $targetUser = User::factory()->create(['role' => 'user', 'status' => 'active']);

        // Criar rede para o usuário alvo
        User::factory(2)->create(['sponsor_id' => $targetUser->id, 'status' => 'active']);
        User::factory(1)->create(['sponsor_id' => $targetUser->id, 'status' => 'inactive']);

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/v1/customer/users/{$targetUser->uuid}/statistics");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'statistics' => [
                    'total_network',
                    'active_network',
                    'inactive_network',
                    'suspended_network',
                    'account_created_at',
                    'last_login',
                    'user_info' => [
                        'uuid',
                        'name',
                        'email',
                        'role',
                        'status',
                    ],
                ],
            ])
            ->assertJson([
                'statistics' => [
                    'total_network' => 3,
                    'active_network' => 2,
                    'inactive_network' => 1,
                    'suspended_network' => 0,
                    'user_info' => [
                        'uuid' => $targetUser->uuid,
                        'role' => 'user',
                        'status' => 'active',
                    ],
                ],
            ]);
    }

    /**
     * Test regular user cannot view other user's statistics
     */
    public function test_regular_user_cannot_view_other_user_statistics(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $otherUser = User::factory()->create(['role' => 'user']);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/customer/users/{$otherUser->uuid}/statistics");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Acesso negado.',
            ]);
    }

    /**
     * Test network endpoint returns empty when user has no sponsored users
     */
    public function test_network_endpoint_returns_empty_for_user_with_no_network(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/customer/network');

        $response->assertStatus(200)
            ->assertJson([
                'network' => [
                    'data' => [],
                    'total' => 0,
                ],
                'total_network' => 0,
            ]);
    }

    /**
     * Test statistics show zero counts when user has no network
     */
    public function test_statistics_show_zero_counts_for_user_with_no_network(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/customer/statistics');

        $response->assertStatus(200)
            ->assertJson([
                'statistics' => [
                    'total_network' => 0,
                    'active_network' => 0,
                    'inactive_network' => 0,
                    'suspended_network' => 0,
                ],
            ]);
    }

    /**
     * Test network endpoint handles non-existent user UUID
     */
    public function test_network_endpoint_handles_non_existent_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $fakeUuid = $this->faker->uuid();

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/v1/customer/users/{$fakeUuid}/network");

        $response->assertStatus(404);
    }

    /**
     * Test sponsor endpoint handles non-existent user UUID
     */
    public function test_sponsor_endpoint_handles_non_existent_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $fakeUuid = $this->faker->uuid();

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/v1/customer/users/{$fakeUuid}/sponsor");

        $response->assertStatus(404);
    }

    /**
     * Test statistics endpoint handles non-existent user UUID
     */
    public function test_statistics_endpoint_handles_non_existent_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $fakeUuid = $this->faker->uuid();

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/v1/customer/users/{$fakeUuid}/statistics");

        $response->assertStatus(404);
    }

    /**
     * Test unauthenticated access to network endpoints
     */
    public function test_unauthenticated_access_to_network_endpoints(): void
    {
        $user = User::factory()->create();

        $endpoints = [
            '/api/v1/customer/network',
            '/api/v1/customer/sponsor',
            '/api/v1/customer/statistics',
            "/api/v1/customer/users/{$user->uuid}/network",
            "/api/v1/customer/users/{$user->uuid}/sponsor",
            "/api/v1/customer/users/{$user->uuid}/statistics",
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $response->assertStatus(401);
        }
    }

    /**
     * Test network relationships are loaded correctly
     */
    public function test_network_relationships_loaded_correctly(): void
    {
        $sponsor = User::factory()->create(['name' => 'Main Sponsor']);
        $user = User::factory()->create(['sponsor_id' => $sponsor->id]);

        // Criar usuários patrocinados com seus próprios patrocinadores
        $sponsored = User::factory()->create([
            'sponsor_id' => $user->id,
            'name' => 'Sponsored User',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/customer/network');

        $response->assertStatus(200);

        $networkData = $response->json('network.data');
        $this->assertCount(1, $networkData);

        // Verificar que o relationship sponsor está carregado
        $this->assertArrayHasKey('sponsor', $networkData[0]);
        $this->assertEquals($user->id, $networkData[0]['sponsor']['id']);
    }

    /**
     * Test multi-level network structure
     */
    public function test_multi_level_network_structure(): void
    {
        // Criar hierarquia: Sponsor -> User -> Level1 -> Level2
        $sponsor = User::factory()->create(['name' => 'Top Sponsor']);
        $user = User::factory()->create(['sponsor_id' => $sponsor->id, 'name' => 'Main User']);
        $level1 = User::factory()->create(['sponsor_id' => $user->id, 'name' => 'Level 1']);
        $level2 = User::factory()->create(['sponsor_id' => $level1->id, 'name' => 'Level 2']);

        Sanctum::actingAs($user);

        // User deve ver apenas Level 1 (seus diretos patrocinados)
        $response = $this->getJson('/api/v1/customer/network');
        $response->assertStatus(200)
            ->assertJson([
                'total_network' => 1,
                'user_info' => [
                    'name' => 'Main User',
                ],
            ]);

        $networkData = $response->json('network.data');
        $this->assertEquals('Level 1', $networkData[0]['name']);

        // Verificar estatísticas do Level 1
        Sanctum::actingAs($level1);
        $response = $this->getJson('/api/v1/customer/statistics');
        $response->assertStatus(200)
            ->assertJson([
                'statistics' => [
                    'total_network' => 1, // Level 2
                ],
            ]);
    }
}
