<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerProfileTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test customer can view their own profile
     */
    public function test_customer_can_view_own_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'John Customer',
            'email' => 'customer@example.com',
            'role' => 'user',
        ]);
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/customer/me');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'user' => [
                        'id',
                        'uuid',
                        'name',
                        'email',
                        'username',
                        'created_at',
                        'updated_at'
                    ]
                ])
                ->assertJson([
                    'user' => [
                        'name' => 'John Customer',
                        'email' => 'customer@example.com',
                        'role' => 'user'
                    ]
                ]);
    }

    /**
     * Test customer cannot view profile without authentication
     */
    public function test_customer_cannot_view_profile_without_authentication(): void
    {
        $response = $this->getJson('/api/v1/customer/me');

        $response->assertStatus(401)
                ->assertJson([
                    'message' => 'Unauthenticated.'
                ]);
    }

    /**
     * Test customer can update their profile with valid data
     */
    public function test_customer_can_update_profile_with_valid_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'phone' => '11999999999',
            'email' => 'old@example.com',
            'username' => 'oldusername'
        ]);
        
        Sanctum::actingAs($user);

        $updateData = [
            'name' => 'New Customer Name',
            'phone' => '11888888888',
            'email' => 'new@example.com',
            'username' => 'newusername'
        ];

        $response = $this->putJson('/api/v1/customer/profile', $updateData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'user'
                ])
                ->assertJson([
                    'message' => 'Perfil atualizado com sucesso'
                ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Customer Name',
            'phone' => '11888888888',
            'email' => 'new@example.com',
            'username' => 'newusername'
        ]);
    }

    /**
     * Test customer can change password with valid data
     */
    public function test_customer_can_change_password_with_valid_data(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword123'),
        ]);
        
        Sanctum::actingAs($user);

        $passwordData = [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];

        $response = $this->postJson('/api/v1/customer/change-password', $passwordData);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Password changed successfully'
                ]);

        // Verify password was changed
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    /**
     * Test customer can view their network
     */
    public function test_customer_can_view_their_network(): void
    {
        $user = User::factory()->create();
        $sponsored1 = User::factory()->create(['sponsor_id' => $user->id]);
        $sponsored2 = User::factory()->create(['sponsor_id' => $user->id]);
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/customer/network');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'network' => [
                        'data',
                        'current_page',
                        'last_page',
                        'total'
                    ],
                    'total_network',
                    'user_info' => [
                        'uuid',
                        'name',
                        'email'
                    ]
                ])
                ->assertJson([
                    'total_network' => 2
                ]);
    }

    /**
     * Test customer can view their sponsor information
     */
    public function test_customer_can_view_their_sponsor(): void
    {
        $sponsor = User::factory()->create([
            'name' => 'Sponsor User',
            'email' => 'sponsor@example.com',
        ]);
        
        $user = User::factory()->create([
            'sponsor_id' => $sponsor->id,
        ]);
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/customer/sponsor');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'sponsor' => [
                        'uuid',
                        'name',
                        'email',
                        'phone',
                        'created_at'
                    ],
                    'user_info' => [
                        'uuid',
                        'name',
                        'email'
                    ]
                ])
                ->assertJson([
                    'sponsor' => [
                        'name' => 'Sponsor User',
                        'email' => 'sponsor@example.com'
                    ]
                ]);
    }

    /**
     * Test customer without sponsor gets not found response
     */
    public function test_customer_without_sponsor_gets_not_found(): void
    {
        $user = User::factory()->create([
            'sponsor_id' => null,
        ]);
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/customer/sponsor');

        $response->assertStatus(404)
                ->assertJson([
                    'message' => 'Você não possui patrocinador'
                ]);
    }

    /**
     * Test customer can view their statistics
     */
    public function test_customer_can_view_their_statistics(): void
    {
        $user = User::factory()->create();
        
        // Create sponsored users with different statuses
        User::factory()->create(['sponsor_id' => $user->id, 'status' => 'active']);
        User::factory()->create(['sponsor_id' => $user->id, 'status' => 'active']);
        User::factory()->create(['sponsor_id' => $user->id, 'status' => 'inactive']);
        User::factory()->create(['sponsor_id' => $user->id, 'status' => 'suspended']);
        
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
                            'status'
                        ]
                    ]
                ])
                ->assertJson([
                    'statistics' => [
                        'total_network' => 4,
                        'active_network' => 2,
                        'inactive_network' => 1,
                        'suspended_network' => 1
                    ]
                ]);
    }

    /**
     * Test customer cannot view other user network without permission
     */
    public function test_customer_cannot_view_other_user_network(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/customer/users/{$otherUser->uuid}/network");

        $response->assertStatus(403)
                ->assertJson([
                    'message' => 'Acesso negado.'
                ]);
    }

    /**
     * Test admin can view any user's network
     */
    public function test_admin_can_view_any_user_network(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $sponsored = User::factory()->create(['sponsor_id' => $user->id]);
        
        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/v1/customer/users/{$user->uuid}/network");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'network',
                    'total_network',
                    'target_user'
                ]);
    }

    /**
     * Test customer cannot change password with wrong current password
     */
    public function test_customer_cannot_change_password_with_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword123'),
        ]);
        
        Sanctum::actingAs($user);

        $passwordData = [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];

        $response = $this->postJson('/api/v1/customer/change-password', $passwordData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['current_password']);
    }

    /**
     * Test customer cannot update profile with invalid data
     */
    public function test_customer_cannot_update_profile_with_invalid_data(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $updateData = [
            'name' => '', // empty name
            'email' => 'invalid-email',
        ];

        $response = $this->putJson('/api/v1/customer/profile', $updateData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'email']);
    }

    /**
     * Test customer cannot update profile without data
     */
    public function test_customer_cannot_update_profile_without_data(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/customer/profile', []);

        $response->assertStatus(422)
                ->assertJson([
                    'message' => 'Nenhuma alteração fornecida.'
                ]);
    }

    /**
     * Test endpoints without authentication return 401
     */
    public function test_endpoints_without_authentication_return_401(): void
    {
        $endpoints = [
            ['method' => 'GET', 'url' => '/api/v1/customer/network'],
            ['method' => 'GET', 'url' => '/api/v1/customer/sponsor'],
            ['method' => 'GET', 'url' => '/api/v1/customer/statistics'],
            ['method' => 'PUT', 'url' => '/api/v1/customer/profile'],
            ['method' => 'POST', 'url' => '/api/v1/customer/change-password']
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->json($endpoint['method'], $endpoint['url'], []);
            $response->assertStatus(401)
                    ->assertJson(['message' => 'Unauthenticated.']);
        }
    }

    /**
     * Test customer with no network sees empty results
     */
    public function test_customer_with_no_network_sees_empty_results(): void
    {
        $user = User::factory()->create();
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/customer/network');

        $response->assertStatus(200)
                ->assertJson([
                    'total_network' => 0
                ]);
    }

    /**
     * Test endpoints return 404 for non-existent user UUID
     */
    public function test_endpoints_return_404_for_non_existent_uuid(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user);

        $nonExistentUuid = 'non-existent-uuid';

        $endpoints = [
            "/api/v1/customer/users/{$nonExistentUuid}/network",
            "/api/v1/customer/users/{$nonExistentUuid}/sponsor",
            "/api/v1/customer/users/{$nonExistentUuid}/statistics"
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $response->assertStatus(404);
        }
    }
}