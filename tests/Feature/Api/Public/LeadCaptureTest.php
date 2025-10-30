<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Public;

use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadCaptureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful lead capture.
     */
    public function test_lead_capture_success(): void
    {
        $payload = [
            'name' => 'João Silva',
            'email' => 'joao.silva@email.com',
            'phone' => '11999887766',
            'utm_source' => 'facebook',
            'utm_campaign' => 'teste-api',
            'utm_medium' => 'cpc',
            'utm_term' => 'sorteios',
            'utm_content' => 'banner-azul',
            'preferences' => ['sorteios', 'promoções'],
            'landing_page' => 'https://premiaclub.com.br/pre-lancamento',
        ];

        $response = $this->postJson('/api/v1/public/leads/capture', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'subscriber_uuid',
                    'email',
                    'status',
                    'tracking_source',
                    'tracking_campaign',
                    'next_steps',
                ],
                'meta' => [
                    'execution_time_ms',
                ],
            ]);

        // Verify subscriber was created
        $this->assertDatabaseHas('subscribers', [
            'name' => 'João Silva',
            'email' => 'joao.silva@email.com',
            'phone' => '11999887766',
            'utm_source' => 'facebook',
            'utm_campaign' => 'teste-api',
            'status' => 'active', // Status ativo automaticamente
        ]);
    }

    /**
     * Test lead capture validation errors.
     */
    public function test_lead_capture_validation_errors(): void
    {
        $payload = [
            'email' => 'invalid-email',
            'phone' => '123', // Too short
        ];

        $response = $this->postJson('/api/v1/public/leads/capture', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'phone']);
    }

    /**
     * Test duplicate email handling.
     */
    public function test_duplicate_email_handling(): void
    {
        // Create an existing subscriber
        Subscriber::factory()->create([
            'email' => 'joao.silva@email.com',
        ]);

        $payload = [
            'name' => 'João Silva',
            'email' => 'joao.silva@email.com',
            'utm_source' => 'google',
        ];

        $response = $this->postJson('/api/v1/public/leads/capture', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'E-mail já cadastrado. Dados atualizados com sucesso.',
            ]);
    }

    /**
     * Test lead status check.
     */
    public function test_lead_status_check(): void
    {
        $subscriber = Subscriber::factory()->create();

        $response = $this->getJson("/api/v1/public/leads/status/{$subscriber->uuid}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'uuid',
                    'status',
                    'subscribed_at',
                    'preferences',
                ],
            ]);
    }

    /**
     * Test lead unsubscribe.
     */
    public function test_lead_unsubscribe(): void
    {
        $subscriber = Subscriber::factory()->create([
            'status' => 'active',
        ]);

        $response = $this->deleteJson("/api/v1/public/leads/unsubscribe/{$subscriber->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Lead descadastrado com sucesso.',
            ]);

        // Verify subscriber was marked as unsubscribed
        $this->assertDatabaseHas('subscribers', [
            'uuid' => $subscriber->uuid,
            'status' => 'unsubscribed',
        ]);
    }

    /**
     * Test rate limiting.
     */
    public function test_rate_limiting(): void
    {
        $payload = [
            'name' => 'João Silva',
            'email' => 'joao.silva@email.com',
        ];

        // Make 6 requests (limit is 5 per minute)
        for ($i = 0; $i < 6; $i++) {
            $payload['email'] = "joao{$i}@email.com";
            $response = $this->postJson('/api/v1/public/leads/capture', $payload);
            
            if ($i < 5) {
                $response->assertStatus(201);
            } else {
                $response->assertStatus(429); // Too Many Requests
            }
        }
    }
}