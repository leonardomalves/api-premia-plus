<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdministratorUsersTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test admin can list all users with pagination
     */
    public function test_admin_can_list_all_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Criar usuários variados
        User::factory(10)->create(['role' => 'user']);
        User::factory(3)->create(['role' => 'admin']);
        User::factory(2)->create(['role' => 'moderator']);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/administrator/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'users' => [
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
                            'phone',
                            'sponsor_id',
                            'created_at',
                            'updated_at',
                            'sponsor',
                        ],
                    ],
                    'first_page_url',
                    'from',
                    'last_page',
                    'per_page',
                    'total',
                ],
                'filters' => [
                    'search',
                    'role',
                    'status',
                    'sponsor_uuid',
                ],
            ]);

        // Total deve incluir todos os usuários criados + admin inicial
        $this->assertEquals(16, $response->json('users.total'));
    }

    /**
     * Test admin can search users by name, email, or username
     */
    public function test_admin_can_search_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $specificUser = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'username' => 'johndoe',
        ]);

        User::factory(5)->create(); // Ruído

        Sanctum::actingAs($admin);

        // Buscar por nome
        $response = $this->getJson('/api/v1/administrator/users?search=John');
        $response->assertStatus(200);
        $users = $response->json('users.data');
        $this->assertCount(1, $users);
        $this->assertEquals('John Doe', $users[0]['name']);

        // Buscar por email
        $response = $this->getJson('/api/v1/administrator/users?search=john.doe');
        $response->assertStatus(200);
        $users = $response->json('users.data');
        $this->assertCount(1, $users);
        $this->assertEquals('john.doe@example.com', $users[0]['email']);

        // Buscar por username
        $response = $this->getJson('/api/v1/administrator/users?search=johndoe');
        $response->assertStatus(200);
        $users = $response->json('users.data');
        $this->assertCount(1, $users);
        $this->assertEquals('johndoe', $users[0]['username']);
    }

    /**
     * Test admin can filter users by role
     */
    public function test_admin_can_filter_users_by_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        User::factory(3)->create(['role' => 'user']);
        User::factory(2)->create(['role' => 'moderator']);
        User::factory(1)->create(['role' => 'support']);

        Sanctum::actingAs($admin);

        // Filtrar por role user
        $response = $this->getJson('/api/v1/administrator/users?role=user');
        $response->assertStatus(200);
        $users = $response->json('users.data');
        $this->assertCount(3, $users);
        foreach ($users as $user) {
            $this->assertEquals('user', $user['role']);
        }

        // Filtrar por role moderator
        $response = $this->getJson('/api/v1/administrator/users?role=moderator');
        $response->assertStatus(200);
        $users = $response->json('users.data');
        $this->assertCount(2, $users);
        foreach ($users as $user) {
            $this->assertEquals('moderator', $user['role']);
        }
    }

    /**
     * Test admin can filter users by status
     */
    public function test_admin_can_filter_users_by_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        User::factory(3)->create(['status' => 'active']);
        User::factory(2)->create(['status' => 'inactive']);
        User::factory(1)->create(['status' => 'suspended']);

        Sanctum::actingAs($admin);

        // Filtrar por status active
        $response = $this->getJson('/api/v1/administrator/users?status=active');
        $response->assertStatus(200);
        $users = $response->json('users.data');
        $this->assertCount(4, $users); // 3 criados + admin
        foreach ($users as $user) {
            $this->assertEquals('active', $user['status']);
        }

        // Filtrar por status suspended
        $response = $this->getJson('/api/v1/administrator/users?status=suspended');
        $response->assertStatus(200);
        $users = $response->json('users.data');
        $this->assertCount(1, $users);
        foreach ($users as $user) {
            $this->assertEquals('suspended', $user['status']);
        }
    }

    /**
     * Test admin can filter users by sponsor
     */
    public function test_admin_can_filter_users_by_sponsor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sponsor = User::factory()->create(['name' => 'Main Sponsor']);

        User::factory(3)->create(['sponsor_id' => $sponsor->id]);
        User::factory(2)->create(['sponsor_id' => null]); // Sem patrocinador

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/v1/administrator/users?sponsor_uuid={$sponsor->uuid}");

        $response->assertStatus(200);
        $users = $response->json('users.data');
        $this->assertCount(3, $users);
        foreach ($users as $user) {
            $this->assertEquals($sponsor->id, $user['sponsor_id']);
        }
    }

    /**
     * Test admin can view specific user details
     */
    public function test_admin_can_view_specific_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sponsor = User::factory()->create(['name' => 'Sponsor User']);
        $targetUser = User::factory()->create([
            'name' => 'Target User',
            'email' => 'target@example.com',
            'sponsor_id' => $sponsor->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/v1/administrator/users/{$targetUser->uuid}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'uuid',
                    'name',
                    'email',
                    'username',
                    'role',
                    'status',
                    'phone',
                    'sponsor_id',
                    'created_at',
                    'updated_at',
                    'sponsor' => [
                        'id',
                        'uuid',
                        'name',
                        'email',
                    ],
                ],
            ])
            ->assertJson([
                'user' => [
                    'uuid' => $targetUser->uuid,
                    'name' => 'Target User',
                    'email' => 'target@example.com',
                    'sponsor' => [
                        'uuid' => $sponsor->uuid,
                        'name' => 'Sponsor User',
                    ],
                ],
            ]);
    }

    /**
     * Test admin can create new user
     */
    public function test_admin_can_create_new_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sponsor = User::factory()->create();

        Sanctum::actingAs($admin);

        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'username' => 'newuser',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '123456789',
            'role' => 'user',
            'status' => 'active',
            'sponsor_uuid' => $sponsor->uuid,
        ];

        $response = $this->postJson('/api/v1/administrator/users', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'uuid',
                    'name',
                    'email',
                    'username',
                    'role',
                    'status',
                    'phone',
                    'sponsor_id',
                    'created_at',
                    'updated_at',
                    'sponsor',
                ],
            ])
            ->assertJson([
                'message' => 'Usuário criado com sucesso',
                'user' => [
                    'name' => 'New User',
                    'email' => 'newuser@example.com',
                    'username' => 'newuser',
                    'role' => 'user',
                    'status' => 'active',
                    'phone' => '123456789',
                    'sponsor_id' => $sponsor->id,
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'username' => 'newuser',
            'sponsor_id' => $sponsor->id,
        ]);
    }

    /**
     * Test admin cannot create user with duplicate email
     */
    public function test_admin_cannot_create_user_with_duplicate_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        Sanctum::actingAs($admin);

        $userData = [
            'name' => 'New User',
            'email' => 'existing@example.com', // Email duplicado
            'username' => 'newuser',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/v1/administrator/users', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test admin can update existing user
     */
    public function test_admin_can_update_existing_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $targetUser = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'role' => 'user',
            'status' => 'active',
        ]);
        $newSponsor = User::factory()->create();

        Sanctum::actingAs($admin);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'role' => 'moderator',
            'status' => 'inactive',
            'sponsor_uuid' => $newSponsor->uuid,
        ];

        $response = $this->putJson("/api/v1/administrator/users/{$targetUser->uuid}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Usuário atualizado com sucesso',
                'user' => [
                    'uuid' => $targetUser->uuid,
                    'name' => 'Updated Name',
                    'email' => 'updated@example.com',
                    'role' => 'moderator',
                    'status' => 'inactive',
                    'sponsor_id' => $newSponsor->id,
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'uuid' => $targetUser->uuid,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'role' => 'moderator',
            'status' => 'inactive',
            'sponsor_id' => $newSponsor->id,
        ]);
    }

    /**
     * Test admin can delete user
     */
    public function test_admin_can_delete_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $targetUser = User::factory()->create(['name' => 'To Delete']);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/v1/administrator/users/{$targetUser->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Usuário excluído com sucesso',
            ]);

        $this->assertSoftDeleted('users', [
            'uuid' => $targetUser->uuid,
        ]);
    }

    /**
     * Test admin cannot delete themselves
     */
    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/v1/administrator/users/{$admin->uuid}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Você não pode excluir sua própria conta.',
            ]);

        $this->assertDatabaseHas('users', [
            'uuid' => $admin->uuid,
        ]);
    }

    /**
     * Test admin can view user network
     */
    public function test_admin_can_view_user_network(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $targetUser = User::factory()->create(['name' => 'Network Owner']);

        // Criar rede para o usuário alvo
        User::factory(3)->create(['sponsor_id' => $targetUser->id]);

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/v1/administrator/users/{$targetUser->uuid}/network");

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
                    'name' => 'Network Owner',
                ],
            ]);
    }

    /**
     * Test admin can view user sponsor
     */
    public function test_admin_can_view_user_sponsor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sponsor = User::factory()->create(['name' => 'The Sponsor']);
        $targetUser = User::factory()->create([
            'sponsor_id' => $sponsor->id,
            'name' => 'Sponsored User',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/v1/administrator/users/{$targetUser->uuid}/sponsor");

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
                    'uuid' => $sponsor->uuid,
                    'name' => 'The Sponsor',
                ],
                'target_user' => [
                    'uuid' => $targetUser->uuid,
                    'name' => 'Sponsored User',
                ],
            ]);
    }

    /**
     * Test admin can view user statistics
     */
    public function test_admin_can_view_user_statistics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $targetUser = User::factory()->create(['status' => 'active']);

        // Criar rede com diferentes status
        User::factory(2)->create(['sponsor_id' => $targetUser->id, 'status' => 'active']);
        User::factory(1)->create(['sponsor_id' => $targetUser->id, 'status' => 'inactive']);
        User::factory(1)->create(['sponsor_id' => $targetUser->id, 'status' => 'suspended']);

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/v1/administrator/users/{$targetUser->uuid}/statistics");

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
                    'total_network' => 4,
                    'active_network' => 2,
                    'inactive_network' => 1,
                    'suspended_network' => 1,
                    'user_info' => [
                        'uuid' => $targetUser->uuid,
                        'status' => 'active',
                    ],
                ],
            ]);
    }

    /**
     * Test admin can get system statistics
     */
    public function test_admin_can_get_system_statistics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sponsor = User::factory()->create();

        // Criar usuários variados
        User::factory(3)->create(['role' => 'user', 'status' => 'active']);
        User::factory(2)->create(['role' => 'user', 'status' => 'inactive']);
        User::factory(1)->create(['role' => 'moderator', 'status' => 'suspended']);
        User::factory(2)->create(['sponsor_id' => $sponsor->id]); // Com patrocinador
        User::factory(1)->create(['sponsor_id' => null]); // Sem patrocinador

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
                    'users_without_sponsors',
                ],
            ]);

        $stats = $response->json('system_statistics');
        $this->assertEquals(11, $stats['total_users']); // Todos os usuários
        $this->assertEquals(8, $stats['active_users']); // Default status é active
        $this->assertEquals(2, $stats['inactive_users']);
        $this->assertEquals(1, $stats['suspended_users']);
        $this->assertEquals(2, $stats['users_with_sponsors']);
    }

    /**
     * Test admin can perform bulk update
     */
    public function test_admin_can_perform_bulk_update(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $users = User::factory(3)->create(['role' => 'user', 'status' => 'active']);

        Sanctum::actingAs($admin);

        $bulkData = [
            'user_uuids' => $users->pluck('uuid')->toArray(),
            'updates' => [
                'role' => 'moderator',
                'status' => 'inactive',
            ],
        ];

        $response = $this->postJson('/api/v1/administrator/users/bulk-update', $bulkData);

        $response->assertStatus(200)
            ->assertJson([
                'updated_count' => 3,
                'errors' => [],
            ]);

        foreach ($users as $user) {
            $this->assertDatabaseHas('users', [
                'uuid' => $user->uuid,
                'role' => 'moderator',
                'status' => 'inactive',
            ]);
        }
    }

    /**
     * Test admin can perform bulk delete
     */
    public function test_admin_can_perform_bulk_delete(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $users = User::factory(3)->create();

        Sanctum::actingAs($admin);

        $bulkData = [
            'user_uuids' => $users->pluck('uuid')->toArray(),
        ];

        $response = $this->postJson('/api/v1/administrator/users/bulk-delete', $bulkData);

        $response->assertStatus(200)
            ->assertJson([
                'deleted_count' => 3,
                'errors' => [],
            ]);

        foreach ($users as $user) {
            $this->assertSoftDeleted('users', [
                'uuid' => $user->uuid,
            ]);
        }
    }

    /**
     * Test admin cannot bulk delete themselves
     */
    public function test_admin_cannot_bulk_delete_themselves(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherUser = User::factory()->create();

        Sanctum::actingAs($admin);

        $bulkData = [
            'user_uuids' => [$admin->uuid, $otherUser->uuid],
        ];

        $response = $this->postJson('/api/v1/administrator/users/bulk-delete', $bulkData);

        $response->assertStatus(200);

        $responseData = $response->json();
        $this->assertEquals(1, $responseData['deleted_count']); // Apenas o outro usuário
        $this->assertCount(1, $responseData['errors']); // Erro para o admin

        // Admin não foi deletado
        $this->assertDatabaseHas('users', ['uuid' => $admin->uuid]);
        // Outro usuário foi deletado
        $this->assertSoftDeleted('users', ['uuid' => $otherUser->uuid]);
    }

    /**
     * Test admin can export users data
     */
    public function test_admin_can_export_users_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sponsor = User::factory()->create(['name' => 'Export Sponsor']);

        User::factory(3)->create(['sponsor_id' => $sponsor->id]);
        User::factory(2)->create(['role' => 'moderator']);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/administrator/users/export');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'total',
                'users' => [
                    '*' => [
                        'uuid',
                        'name',
                        'email',
                        'username',
                        'role',
                        'status',
                        'phone',
                        'sponsor_uuid',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        $exportData = $response->json();
        $this->assertEquals(7, $exportData['total']); // Todos os usuários criados
        $this->assertCount(7, $exportData['users']);
    }

    /**
     * Test admin can get dashboard overview
     */
    public function test_admin_can_get_dashboard_overview(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Criar dados para dashboard
        $topSponsor = User::factory()->create(['name' => 'Top Sponsor']);
        User::factory(5)->create(['sponsor_id' => $topSponsor->id]); // Rede grande
        User::factory(2)->create(['sponsor_id' => null]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/administrator/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'summary' => [
                    'total_users',
                    'active_users',
                    'inactive_users',
                    'suspended_users',
                    'new_users_last_30_days',
                ],
                'top_sponsors' => [
                    '*' => [
                        'uuid',
                        'name',
                        'email',
                        'sponsored_count',
                    ],
                ],
                'recent_users' => [
                    '*' => [
                        'uuid',
                        'name',
                        'email',
                        'role',
                        'status',
                        'created_at',
                    ],
                ],
            ]);

        $dashboardData = $response->json();
        $this->assertEquals(9, $dashboardData['summary']['total_users']);
        $this->assertEquals(5, $dashboardData['top_sponsors'][0]['sponsored_count']);
        $this->assertEquals('Top Sponsor', $dashboardData['top_sponsors'][0]['name']);
    }

    /**
     * Test non-admin cannot access admin endpoints
     */
    public function test_non_admin_cannot_access_admin_endpoints(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $targetUser = User::factory()->create();

        Sanctum::actingAs($user);

        $adminEndpoints = [
            ['GET', '/api/v1/administrator/users'],
            ['GET', "/api/v1/administrator/users/{$targetUser->uuid}"],
            ['POST', '/api/v1/administrator/users'],
            ['PUT', "/api/v1/administrator/users/{$targetUser->uuid}"],
            ['DELETE', "/api/v1/administrator/users/{$targetUser->uuid}"],
            ['GET', "/api/v1/administrator/users/{$targetUser->uuid}/network"],
            ['GET', "/api/v1/administrator/users/{$targetUser->uuid}/sponsor"],
            ['GET', "/api/v1/administrator/users/{$targetUser->uuid}/statistics"],
            ['GET', '/api/v1/administrator/statistics'],
            ['GET', '/api/v1/administrator/dashboard'],
            ['POST', '/api/v1/administrator/users/bulk-update'],
            ['POST', '/api/v1/administrator/users/bulk-delete'],
            ['POST', '/api/v1/administrator/users/export'],
        ];

        foreach ($adminEndpoints as [$method, $endpoint]) {
            $response = $this->json($method, $endpoint, []);
            $response->assertStatus(403)
                ->assertJson([
                    'message' => 'Acesso negado. Apenas administradores podem acessar esta funcionalidade.',
                    'error' => 'Forbidden',
                ]);
        }
    }

    /**
     * Test unauthenticated user cannot access admin endpoints
     */
    public function test_unauthenticated_user_cannot_access_admin_endpoints(): void
    {
        $targetUser = User::factory()->create();

        $adminEndpoints = [
            ['GET', '/api/v1/administrator/users'],
            ['GET', "/api/v1/administrator/users/{$targetUser->uuid}"],
            ['POST', '/api/v1/administrator/users'],
            ['PUT', "/api/v1/administrator/users/{$targetUser->uuid}"],
            ['DELETE', "/api/v1/administrator/users/{$targetUser->uuid}"],
            ['GET', '/api/v1/administrator/statistics'],
            ['GET', '/api/v1/administrator/dashboard'],
        ];

        foreach ($adminEndpoints as [$method, $endpoint]) {
            $response = $this->json($method, $endpoint, []);
            $response->assertStatus(401);
        }
    }

    /**
     * Test admin endpoints handle non-existent user UUIDs
     */
    public function test_admin_endpoints_handle_non_existent_uuids(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $fakeUuid = $this->faker->uuid();

        Sanctum::actingAs($admin);

        $endpoints = [
            ['GET', "/api/v1/administrator/users/{$fakeUuid}"],
            ['PUT', "/api/v1/administrator/users/{$fakeUuid}"],
            ['DELETE', "/api/v1/administrator/users/{$fakeUuid}"],
            ['GET', "/api/v1/administrator/users/{$fakeUuid}/network"],
            ['GET', "/api/v1/administrator/users/{$fakeUuid}/sponsor"],
            ['GET', "/api/v1/administrator/users/{$fakeUuid}/statistics"],
        ];

        foreach ($endpoints as [$method, $endpoint]) {
            $response = $this->json($method, $endpoint, []);
            $response->assertStatus(404);
        }
    }
}
