<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerCartTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test customer can add item to empty cart
     */
    public function test_customer_can_add_item_to_empty_cart(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $plan = Plan::factory()->active()->create([
            'name' => 'Premium Plan',
            'price' => 99.99
        ]);
        
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/customer/cart/add', [
            'plan_uuid' => $plan->uuid
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'cart' => [
                            'id',
                            'uuid',
                            'user_id',
                            'plan_id',
                            'status',
                            'created_at',
                            'updated_at',
                            'plan' => [
                                'id',
                                'uuid',
                                'name',
                                'price'
                            ]
                        ],
                        'action'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Item adicionado ao carrinho com sucesso',
                    'data' => [
                        'action' => 'created',
                        'cart' => [
                            'user_id' => $user->id,
                            'plan_id' => $plan->id,
                            'status' => 'active',
                            'plan' => [
                                'uuid' => $plan->uuid,
                                'name' => 'Premium Plan'
                            ]
                        ]
                    ]
                ]);

        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active'
        ]);
    }

    /**
     * Test customer can update existing cart item
     */
    public function test_customer_can_update_existing_cart_item(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $oldPlan = Plan::factory()->active()->create(['name' => 'Old Plan']);
        $newPlan = Plan::factory()->active()->create(['name' => 'New Plan']);
        
        // Criar carrinho existente
        $existingCart = Cart::create([
            'uuid' => $this->faker->uuid(),
            'user_id' => $user->id,
            'plan_id' => $oldPlan->id,
            'status' => 'active'
        ]);
        
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/customer/cart/add', [
            'plan_uuid' => $newPlan->uuid
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Carrinho atualizado com sucesso',
                    'data' => [
                        'action' => 'updated',
                        'cart' => [
                            'id' => $existingCart->id,
                            'user_id' => $user->id,
                            'plan_id' => $newPlan->id,
                            'status' => 'active'
                        ]
                    ]
                ]);

        $this->assertDatabaseHas('carts', [
            'id' => $existingCart->id,
            'plan_id' => $newPlan->id,
            'status' => 'active'
        ]);
    }

    /**
     * Test customer cannot add inactive plan to cart
     */
    public function test_customer_cannot_add_inactive_plan_to_cart(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $inactivePlan = Plan::factory()->create(['status' => 'inactive']);
        
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/customer/cart/add', [
            'plan_uuid' => $inactivePlan->uuid
        ]);

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Plano não encontrado ou inativo'
                ]);

        $this->assertDatabaseMissing('carts', [
            'user_id' => $user->id,
            'plan_id' => $inactivePlan->id
        ]);
    }

    /**
     * Test customer cannot add non-existent plan to cart
     */
    public function test_customer_cannot_add_non_existent_plan_to_cart(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $fakeUuid = $this->faker->uuid();
        
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/customer/cart/add', [
            'plan_uuid' => $fakeUuid
        ]);

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Plano não encontrado ou inativo'
                ]);
    }

    /**
     * Test customer can view empty cart
     */
    public function test_customer_can_view_empty_cart(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/customer/cart');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Carrinho vazio',
                    'data' => [
                        'cart' => null,
                        'total' => 0
                    ]
                ]);
    }

    /**
     * Test customer can view cart with item
     */
    public function test_customer_can_view_cart_with_item(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $plan = Plan::factory()->active()->create([
            'name' => 'Test Plan',
            'price' => 199.99
        ]);
        
        $cart = Cart::create([
            'uuid' => $this->faker->uuid(),
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active'
        ]);
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/customer/cart');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'cart' => [
                            'id',
                            'uuid',
                            'user_id',
                            'plan_id',
                            'status',
                            'plan' => [
                                'id',
                                'uuid',
                                'name',
                                'price'
                            ]
                        ],
                        'total'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Carrinho carregado com sucesso',
                    'data' => [
                        'total' => 1,
                        'cart' => [
                            'id' => $cart->id,
                            'user_id' => $user->id,
                            'plan_id' => $plan->id,
                            'status' => 'active',
                            'plan' => [
                                'uuid' => $plan->uuid,
                                'name' => 'Test Plan',
                                'price' => '199.99'
                            ]
                        ]
                    ]
                ]);
    }

    /**
     * Test customer can remove item from cart
     */
    public function test_customer_can_remove_item_from_cart(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $plan = Plan::factory()->active()->create();
        
        $cart = Cart::create([
            'uuid' => $this->faker->uuid(),
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active'
        ]);
        
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/customer/cart/remove');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Item removido do carrinho com sucesso'
                ]);

        $this->assertDatabaseHas('carts', [
            'id' => $cart->id,
            'status' => 'abandoned'
        ]);
    }

    /**
     * Test customer cannot remove from empty cart
     */
    public function test_customer_cannot_remove_from_empty_cart(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/customer/cart/remove');

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Carrinho vazio'
                ]);
    }

    /**
     * Test customer can clear cart
     */
    public function test_customer_can_clear_cart(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $plan = Plan::factory()->active()->create();
        
        $cart = Cart::create([
            'uuid' => $this->faker->uuid(),
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active'
        ]);
        
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/customer/cart/clear');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Carrinho limpo com sucesso',
                    'data' => [
                        'cleared_items' => 1
                    ]
                ]);

        $this->assertDatabaseHas('carts', [
            'id' => $cart->id,
            'status' => 'abandoned'
        ]);
    }

    /**
     * Test customer can clear empty cart
     */
    public function test_customer_can_clear_empty_cart(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        
        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/customer/cart/clear');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Carrinho limpo com sucesso',
                    'data' => [
                        'cleared_items' => 0
                    ]
                ]);
    }

    /**
     * Test customer can checkout with cart
     */
    public function test_customer_can_checkout_with_cart(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '123456789',
            'role' => 'user'
        ]);
        
        $plan = Plan::factory()->active()->create([
            'name' => 'Premium Plan',
            'description' => 'Premium subscription',
            'price' => 199.99,
            'grant_tickets' => 10,
            'commission_level_1' => 15.00,
            'commission_level_2' => 10.00,
            'commission_level_3' => 5.00,
            'is_promotional' => true
        ]);
        
        $cart = Cart::create([
            'uuid' => $this->faker->uuid(),
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active'
        ]);
        
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/customer/cart/checkout');

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'order' => [
                            'id',
                            'uuid',
                            'user_id',
                            'user_metadata',
                            'plan_id',
                            'plan_metadata',
                            'status',
                            'created_at',
                            'updated_at',
                            'plan'
                        ],
                        'cart' => [
                            'id',
                            'uuid',
                            'user_id',
                            'plan_id',
                            'order_id',
                            'status'
                        ]
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'message' => 'Compra finalizada com sucesso',
                    'data' => [
                        'order' => [
                            'user_id' => $user->id,
                            'plan_id' => $plan->id,
                            'status' => 'pending',
                            'user_metadata' => [
                                'name' => 'John Doe',
                                'email' => 'john@example.com',
                                'phone' => '123456789'
                            ],
                            'plan_metadata' => [
                                'name' => 'Premium Plan',
                                'description' => 'Premium subscription',
                                'price' => 199.99,
                                'grant_tickets' => 10,
                                'commission_level_1' => 15.00,
                                'commission_level_2' => 10.00,
                                'commission_level_3' => 5.00,
                                'is_promotional' => true
                            ]
                        ],
                        'cart' => [
                            'id' => $cart->id,
                            'status' => 'completed'
                        ]
                    ]
                ]);

        // Verificar se order foi criado
        $order = Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals($plan->id, $order->plan_id);

        // Verificar se cart foi atualizado
        $this->assertDatabaseHas('carts', [
            'id' => $cart->id,
            'order_id' => $order->id,
            'status' => 'completed'
        ]);
    }

    /**
     * Test customer cannot checkout with empty cart
     */
    public function test_customer_cannot_checkout_with_empty_cart(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/customer/cart/checkout');

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Carrinho vazio'
                ]);

        // Verificar que nenhuma order foi criada
        $this->assertDatabaseMissing('orders', [
            'user_id' => $user->id
        ]);
    }

    /**
     * Test multiple users can have separate carts
     */
    public function test_multiple_users_can_have_separate_carts(): void
    {
        $user1 = User::factory()->create(['role' => 'user']);
        $user2 = User::factory()->create(['role' => 'user']);
        $plan1 = Plan::factory()->active()->create(['name' => 'Plan 1']);
        $plan2 = Plan::factory()->active()->create(['name' => 'Plan 2']);
        
        // User 1 adiciona ao carrinho
        Sanctum::actingAs($user1);
        $response1 = $this->postJson('/api/v1/customer/cart/add', [
            'plan_uuid' => $plan1->uuid
        ]);
        $response1->assertStatus(201);

        // User 2 adiciona ao carrinho
        Sanctum::actingAs($user2);
        $response2 = $this->postJson('/api/v1/customer/cart/add', [
            'plan_uuid' => $plan2->uuid
        ]);
        $response2->assertStatus(201);

        // Verificar carrinhos separados
        Sanctum::actingAs($user1);
        $cart1Response = $this->getJson('/api/v1/customer/cart');
        $cart1Response->assertJson([
            'data' => [
                'cart' => [
                    'plan' => [
                        'name' => 'Plan 1'
                    ]
                ]
            ]
        ]);

        Sanctum::actingAs($user2);
        $cart2Response = $this->getJson('/api/v1/customer/cart');
        $cart2Response->assertJson([
            'data' => [
                'cart' => [
                    'plan' => [
                        'name' => 'Plan 2'
                    ]
                ]
            ]
        ]);
    }

    /**
     * Test customer can only have one active cart
     */
    public function test_customer_can_only_have_one_active_cart(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $plan1 = Plan::factory()->active()->create(['name' => 'Plan 1']);
        $plan2 = Plan::factory()->active()->create(['name' => 'Plan 2']);
        
        Sanctum::actingAs($user);

        // Adicionar primeiro plano
        $response1 = $this->postJson('/api/v1/customer/cart/add', [
            'plan_uuid' => $plan1->uuid
        ]);
        $response1->assertStatus(201);

        // Adicionar segundo plano (deve atualizar o existente)
        $response2 = $this->postJson('/api/v1/customer/cart/add', [
            'plan_uuid' => $plan2->uuid
        ]);
        $response2->assertStatus(200)
                 ->assertJson([
                     'data' => ['action' => 'updated']
                 ]);

        // Verificar que existe apenas 1 carrinho ativo
        $activeCarts = Cart::where('user_id', $user->id)
                          ->where('status', 'active')
                          ->count();
        $this->assertEquals(1, $activeCarts);

        // Verificar que o carrinho tem o plano mais recente
        $cart = Cart::where('user_id', $user->id)
                   ->where('status', 'active')
                   ->first();
        $this->assertEquals($plan2->id, $cart->plan_id);
    }

    /**
     * Test cart operations require authentication
     */
    public function test_cart_operations_require_authentication(): void
    {
        $plan = Plan::factory()->active()->create();

        $endpoints = [
            ['POST', '/api/v1/customer/cart/add', ['plan_uuid' => $plan->uuid]],
            ['GET', '/api/v1/customer/cart', []],
            ['DELETE', '/api/v1/customer/cart/remove', []],
            ['DELETE', '/api/v1/customer/cart/clear', []],
            ['POST', '/api/v1/customer/cart/checkout', []]
        ];

        foreach ($endpoints as [$method, $url, $data]) {
            $response = $this->json($method, $url, $data);
            $response->assertStatus(401);
        }
    }

    /**
     * Test cart checkout creates order with correct metadata
     */
    public function test_cart_checkout_creates_order_with_correct_metadata(): void
    {
        $user = User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone' => '987654321'
        ]);
        
        $plan = Plan::factory()->active()->create([
            'name' => 'Gold Plan',
            'description' => 'Gold membership',
            'price' => 299.99,
            'grant_tickets' => 20,
            'commission_level_1' => 20.00,
            'commission_level_2' => 15.00,
            'commission_level_3' => 10.00,
            'is_promotional' => false
        ]);
        
        Cart::create([
            'uuid' => $this->faker->uuid(),
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active'
        ]);
        
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/customer/cart/checkout');

        $order = Order::where('user_id', $user->id)->first();
        
        // Verificar user_metadata
        $this->assertEquals([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone' => '987654321'
        ], $order->user_metadata);

        // Verificar plan_metadata
        $this->assertEquals([
            'name' => 'Gold Plan',
            'description' => 'Gold membership',
            'price' => 299.99,
            'grant_tickets' => 20,
            'commission_level_1' => 20.00,
            'commission_level_2' => 15.00,
            'commission_level_3' => 10.00,
            'is_promotional' => false
        ], $order->plan_metadata);
    }

    /**
     * Test completed cart cannot be modified
     */
    public function test_completed_cart_cannot_be_modified(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $plan = Plan::factory()->active()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        
        // Criar carrinho completed
        $completedCart = Cart::create([
            'uuid' => $this->faker->uuid(),
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'order_id' => $order->id,
            'status' => 'completed'
        ]);
        
        Sanctum::actingAs($user);

        // Tentar visualizar carrinho (deve retornar vazio)
        $response = $this->getJson('/api/v1/customer/cart');
        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'cart' => null,
                        'total' => 0
                    ]
                ]);

        // Tentar remover (deve retornar carrinho vazio)
        $response = $this->deleteJson('/api/v1/customer/cart/remove');
        $response->assertStatus(404)
                ->assertJson([
                    'message' => 'Carrinho vazio'
                ]);

        // Tentar checkout (deve retornar carrinho vazio)
        $response = $this->postJson('/api/v1/customer/cart/checkout');
        $response->assertStatus(404)
                ->assertJson([
                    'message' => 'Carrinho vazio'
                ]);
    }

    /**
     * Test cart add validates plan_uuid parameter
     */
    public function test_cart_add_validates_plan_uuid_parameter(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        
        Sanctum::actingAs($user);

        // Teste sem plan_uuid
        $response = $this->postJson('/api/v1/customer/cart/add', []);
        $response->assertStatus(404); // Plan não encontrado

        // Teste com plan_uuid inválido
        $response = $this->postJson('/api/v1/customer/cart/add', [
            'plan_uuid' => 'invalid-uuid'
        ]);
        $response->assertStatus(404);
    }
}