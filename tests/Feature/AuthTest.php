<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_user_can_register()
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'username' => $this->faker->unique()->userName,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => $this->faker->phoneNumber,
        ];

        $response = $this->postJson('/api/v1/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id', 'name', 'email', 'username', 'phone', 'role', 'status'
                ],
                'access_token',
                'token_type'
            ]);

        $this->assertDatabaseHas('users', [
            'email' => $userData['email'],
            'username' => $userData['username'],
            'role' => 'user',
            'status' => 'active'
        ]);
    }

    public function test_user_can_register_with_sponsor()
    {
        // Criar sponsor
        $sponsor = User::factory()->create(['username' => 'sponsor123']);

        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'username' => $this->faker->unique()->userName,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'sponsor' => $sponsor->username,
        ];

        $response = $this->postJson('/api/v1/register', $userData);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('users', [
            'email' => $userData['email'],
            'sponsor_id' => $sponsor->id
        ]);
    }

    public function test_registration_requires_valid_fields()
    {
        $response = $this->postJson('/api/v1/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'username', 'password']);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user',
                'access_token',
                'token_type'
            ]);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'wrong-password'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson('/api/v1/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Successfully logged out']);
    }

    public function test_user_can_get_profile()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonStructure(['user']);
    }

    public function test_user_can_change_password()
    {
        $user = User::factory()->create([
            'password' => bcrypt('oldpassword')
        ]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token"
        ])->postJson('/api/v1/change-password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Password changed successfully']);
    }
}