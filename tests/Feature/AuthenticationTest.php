<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user for sponsor validation
        User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@test.com',
            'role' => 'admin',
        ]);
    }

    /**
     * Test user registration with valid data
     */
    public function test_register_user_with_valid_data(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'username' => 'johndoe',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '11999999999',
            'sponsor' => 'admin',
        ];

        $response = $this->postJson('/api/v1/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'uuid',
                    'name',
                    'email',
                    'username',
                    'phone',
                    'role',
                    'status',
                    'created_at',
                    'updated_at',
                ],
                'access_token',
                'token_type',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'username' => 'johndoe',
            'name' => 'John Doe',
        ]);
    }

    /**
     * Test user registration with invalid data
     */
    public function test_register_user_with_invalid_data(): void
    {
        $userData = [
            'name' => '',
            'email' => 'invalid-email',
            'username' => '',
            'password' => '123',
            'password_confirmation' => '456',
        ];

        $response = $this->postJson('/api/v1/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'email',
                'username',
                'password',
            ]);
    }

    /**
     * Test user registration with duplicate email
     */
    public function test_register_user_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'john@example.com']);

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'username' => 'johndoe',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '11999999999',
            'sponsor' => 'admin',
        ];

        $response = $this->postJson('/api/v1/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test user registration with duplicate username
     */
    public function test_register_user_with_duplicate_username(): void
    {
        User::factory()->create(['username' => 'johndoe']);

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'username' => 'johndoe',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '11999999999',
            'sponsor' => 'admin',
        ];

        $response = $this->postJson('/api/v1/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    /**
     * Test user registration with invalid sponsor
     */
    public function test_register_user_with_invalid_sponsor(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'username' => 'johndoe',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '11999999999',
            'sponsor' => 'nonexistent',
        ];

        $response = $this->postJson('/api/v1/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sponsor']);
    }

    /**
     * Test user login with valid credentials
     */
    public function test_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
        ]);

        $loginData = [
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/v1/login', $loginData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'uuid',
                    'name',
                    'email',
                    'username',
                    'role',
                    'status',
                ],
                'access_token',
                'token_type',
            ]);

        $this->assertNotEmpty($response->json('access_token'));
    }

    /**
     * Test user login with invalid credentials
     */
    public function test_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
        ]);

        $loginData = [
            'email' => 'john@example.com',
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('/api/v1/login', $loginData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test user login with nonexistent email
     */
    public function test_login_with_nonexistent_email(): void
    {
        $loginData = [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/v1/login', $loginData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test user login with missing data
     */
    public function test_login_with_missing_data(): void
    {
        $response = $this->postJson('/api/v1/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * Test user logout with valid token
     */
    public function test_logout_with_valid_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Successfully logged out',
            ]);
    }

    /**
     * Test user logout without token
     */
    public function test_logout_without_token(): void
    {
        $response = $this->postJson('/api/v1/logout');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * Test token refresh with valid token
     */
    public function test_refresh_token_with_valid_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'access_token',
                'token_type',
            ]);

        $this->assertNotEmpty($response->json('access_token'));
    }

    /**
     * Test token refresh without token
     */
    public function test_refresh_token_without_token(): void
    {
        $response = $this->postJson('/api/v1/refresh');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * Test get authenticated user data
     */
    public function test_get_authenticated_user_data(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'uuid',
                    'name',
                    'email',
                    'username',
                ],
            ])
            ->assertJson([
                'user' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
            ]);
    }

    /**
     * Test get user data without authentication
     */
    public function test_get_user_data_without_authentication(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * Test get user profile
     */
    public function test_get_user_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'role',
                    'status',
                    'sponsor_id',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    /**
     * Test get profile without authentication
     */
    public function test_get_profile_without_authentication(): void
    {
        $response = $this->getJson('/api/v1/profile');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * Test update user profile with valid data
     */
    public function test_update_profile_with_valid_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'phone' => '11999999999',
        ]);

        Sanctum::actingAs($user);

        $updateData = [
            'name' => 'New Name',
            'phone' => '11888888888',
        ];

        $response = $this->putJson('/api/v1/profile', $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'phone' => '11888888888',
        ]);
    }

    /**
     * Test update profile with invalid data
     */
    public function test_update_profile_with_invalid_data(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $updateData = [
            'name' => '', // empty name
        ];

        $response = $this->putJson('/api/v1/profile', $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test update profile without authentication
     */
    public function test_update_profile_without_authentication(): void
    {
        $updateData = [
            'name' => 'New Name',
            'phone' => '11888888888',
        ];

        $response = $this->putJson('/api/v1/profile', $updateData);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * Test change password with valid data
     */
    public function test_change_password_with_valid_data(): void
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

        $response = $this->postJson('/api/v1/change-password', $passwordData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Password changed successfully',
            ]);

        // Verify password was changed
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    /**
     * Test change password with wrong current password
     */
    public function test_change_password_with_wrong_current_password(): void
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

        $response = $this->postJson('/api/v1/change-password', $passwordData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    /**
     * Test change password with mismatched confirmation
     */
    public function test_change_password_with_mismatched_confirmation(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword123'),
        ]);

        Sanctum::actingAs($user);

        $passwordData = [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ];

        $response = $this->postJson('/api/v1/change-password', $passwordData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test change password without authentication
     */
    public function test_change_password_without_authentication(): void
    {
        $passwordData = [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];

        $response = $this->postJson('/api/v1/change-password', $passwordData);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * Test change password with weak password
     */
    public function test_change_password_with_weak_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword123'),
        ]);

        Sanctum::actingAs($user);

        $passwordData = [
            'current_password' => 'oldpassword123',
            'password' => '123', // too short
            'password_confirmation' => '123',
        ];

        $response = $this->postJson('/api/v1/change-password', $passwordData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
