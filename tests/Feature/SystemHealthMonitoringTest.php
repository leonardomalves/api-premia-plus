<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Monitoring\HealthCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SystemHealthMonitoringTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test basic health check endpoint
     */
    public function test_basic_health_check_endpoint(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'timestamp',
                    'version',
                    'environment',
                    'uptime'
                ])
                ->assertJson([
                    'status' => 'ok',
                    'version' => '1.0.0'
                ]);

        $data = $response->json();
        $this->assertNotNull($data['timestamp']);
        $this->assertIsString($data['environment']);
        $this->assertIsNumeric($data['uptime']);
    }

    /**
     * Test detailed health check endpoint
     */
    public function test_detailed_health_check_endpoint(): void
    {
        $response = $this->getJson('/api/v1/health/detailed');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'timestamp',
                    'version',
                    'environment',
                    'checks' => [
                        'database' => [
                            'status',
                            'message'
                        ],
                        'cache' => [
                            'status',
                            'message'
                        ],
                        'storage' => [
                            'status',
                            'message'
                        ],
                        'queue' => [
                            'status',
                            'message'
                        ]
                    ]
                ]);

        $data = $response->json();
        $this->assertContains($data['status'], ['ok', 'error', 'warning']);
        
        // Verify all subsystems were checked
        $this->assertArrayHasKey('database', $data['checks']);
        $this->assertArrayHasKey('cache', $data['checks']);
        $this->assertArrayHasKey('storage', $data['checks']);
        $this->assertArrayHasKey('queue', $data['checks']);
    }

    /**
     * Test health check service database connectivity
     */
    public function test_health_check_service_database(): void
    {
        $service = new HealthCheckService();
        $result = $service->check();

        $this->assertArrayHasKey('checks', $result);
        $this->assertArrayHasKey('database', $result['checks']);
        
        $dbCheck = $result['checks']['database'];
        $this->assertEquals('ok', $dbCheck['status']);
        $this->assertEquals('Database connected', $dbCheck['message']);
        $this->assertArrayHasKey('details', $dbCheck);
        $this->assertArrayHasKey('user_count', $dbCheck['details']);
        $this->assertIsNumeric($dbCheck['details']['user_count']);
    }

    /**
     * Test health check service cache functionality
     */
    public function test_health_check_service_cache(): void
    {
        $service = new HealthCheckService();
        $result = $service->check();

        $cacheCheck = $result['checks']['cache'];
        $this->assertEquals('ok', $cacheCheck['status']);
        $this->assertEquals('Cache working', $cacheCheck['message']);
        $this->assertArrayHasKey('driver', $cacheCheck);
    }

    /**
     * Test health check service storage permissions
     */
    public function test_health_check_service_storage(): void
    {
        $service = new HealthCheckService();
        $result = $service->check();

        $storageCheck = $result['checks']['storage'];
        $this->assertEquals('ok', $storageCheck['status']);
        $this->assertEquals('Storage writable', $storageCheck['message']);
        $this->assertArrayHasKey('free_space', $storageCheck);
        $this->assertStringContainsString('B', $storageCheck['free_space']); // Should contain bytes unit
    }

    /**
     * Test health check service queue system
     */
    public function test_health_check_service_queue(): void
    {
        $service = new HealthCheckService();
        $result = $service->check();

        $queueCheck = $result['checks']['queue'];
        $this->assertContains($queueCheck['status'], ['ok', 'warning', 'error']);
        
        if ($queueCheck['status'] === 'ok') {
            $this->assertEquals('Queue system operational', $queueCheck['message']);
        }
        
        $this->assertArrayHasKey('details', $queueCheck);
        $this->assertArrayHasKey('driver', $queueCheck['details']);
    }

    /**
     * Test overall health status aggregation
     */
    public function test_overall_health_status_aggregation(): void
    {
        $service = new HealthCheckService();
        $result = $service->check();

        $this->assertArrayHasKey('status', $result);
        $this->assertContains($result['status'], ['ok', 'error']);

        // If status is 'ok', all individual checks should be 'ok'
        if ($result['status'] === 'ok') {
            foreach ($result['checks'] as $check) {
                $this->assertEquals('ok', $check['status']);
            }
        }

        // If status is 'error', at least one check should have failed
        if ($result['status'] === 'error') {
            $hasError = collect($result['checks'])->contains(function ($check) {
                return $check['status'] === 'error';
            });
            $this->assertTrue($hasError);
        }
    }

    /**
     * Test test endpoint functionality
     */
    public function test_test_endpoint_functionality(): void
    {
        $response = $this->getJson('/api/v1/test');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'timestamp',
                    'user_agent',
                    'ip',
                    'version'
                ])
                ->assertJson([
                    'message' => 'API Premia Plus funcionando!',
                    'version' => '1.0.0'
                ]);

        $data = $response->json();
        $this->assertNotNull($data['timestamp']);
        $this->assertNotNull($data['user_agent']);
        $this->assertNotNull($data['ip']);
    }

    /**
     * Test user metrics endpoint for authenticated users
     */
    public function test_user_metrics_endpoint_for_authenticated_users(): void
    {
        $user = User::factory()->create();
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/metrics/user');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'user_id',
                    'requests_today',
                    'last_activity'
                ])
                ->assertJson([
                    'user_id' => $user->id
                ]);

        $data = $response->json();
        $this->assertIsNumeric($data['requests_today']);
        $this->assertNotNull($data['last_activity']);
    }

    /**
     * Test user metrics endpoint requires authentication
     */
    public function test_user_metrics_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/metrics/user');

        $response->assertStatus(401);
    }

    /**
     * Test health check with simulated cache failure
     */
    public function test_health_check_with_cache_failure(): void
    {
        // Mock cache to throw exception
        Cache::shouldReceive('put')->andThrow(new \Exception('Cache connection failed'));
        
        $service = new HealthCheckService();
        $result = $service->check();

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('error', $result['checks']['cache']['status']);
        $this->assertEquals('Cache failed', $result['checks']['cache']['message']);
        $this->assertArrayHasKey('error', $result['checks']['cache']);
    }

    /**
     * Test system handles multiple user requests tracking
     */
    public function test_system_handles_multiple_user_requests_tracking(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Simulate requests from different users
        Sanctum::actingAs($user1);
        $response1 = $this->getJson('/api/v1/metrics/user');
        
        Sanctum::actingAs($user2);
        $response2 = $this->getJson('/api/v1/metrics/user');

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $data1 = $response1->json();
        $data2 = $response2->json();

        $this->assertEquals($user1->id, $data1['user_id']);
        $this->assertEquals($user2->id, $data2['user_id']);
        $this->assertIsNumeric($data1['requests_today']);
        $this->assertIsNumeric($data2['requests_today']);
    }

    /**
     * Test health check logs warnings on failures
     */
    public function test_health_check_logs_warnings_on_failures(): void
    {
        // Change cache driver to array to avoid database dependency
        config(['cache.default' => 'array']);
        
        Log::shouldReceive('warning')
           ->once()
           ->with('Health check failed', \Mockery::type('array'));

        // Mock database connection to fail
        DB::shouldReceive('connection')
          ->andReturnSelf();
        
        DB::shouldReceive('getPdo')
          ->andThrow(new \Exception('Connection failed'));
        
        $service = new HealthCheckService();
        $result = $service->check();

        $this->assertEquals('error', $result['status']);
    }

    /**
     * Test queue health check with database driver
     */
    public function test_queue_health_check_with_database_driver(): void
    {
        // Ensure we're using database queue driver
        config(['queue.default' => 'database']);
        
        $service = new HealthCheckService();
        $result = $service->check();

        $queueCheck = $result['checks']['queue'];
        
        if ($queueCheck['status'] === 'ok') {
            $this->assertArrayHasKey('details', $queueCheck);
            $this->assertEquals('database', $queueCheck['details']['driver']);
            $this->assertArrayHasKey('pending_jobs', $queueCheck['details']);
            $this->assertArrayHasKey('failed_jobs', $queueCheck['details']);
            $this->assertIsNumeric($queueCheck['details']['pending_jobs']);
            $this->assertIsNumeric($queueCheck['details']['failed_jobs']);
        }
    }

    /**
     * Test health check detects high failed jobs
     */
    public function test_health_check_detects_high_failed_jobs(): void
    {
        // Create many failed jobs to trigger warning
        for ($i = 0; $i < 15; $i++) {
            DB::table('failed_jobs')->insert([
                'uuid' => Str::uuid(),
                'connection' => 'database',
                'queue' => 'default',
                'payload' => json_encode(['test' => 'job']),
                'exception' => 'Test exception',
                'failed_at' => now()
            ]);
        }

        config(['queue.default' => 'database']);
        
        $service = new HealthCheckService();
        $result = $service->check();

        $queueCheck = $result['checks']['queue'];
        $this->assertEquals('warning', $queueCheck['status']);
        $this->assertEquals('High number of failed jobs', $queueCheck['message']);
        $this->assertGreaterThan(10, $queueCheck['details']['failed_jobs']);
    }

    /**
     * Test storage free space formatting
     */
    public function test_storage_free_space_formatting(): void
    {
        $service = new HealthCheckService();
        $result = $service->check();

        $storageCheck = $result['checks']['storage'];
        $freeSpace = $storageCheck['free_space'];
        
        // Should contain a unit (B, KB, MB, GB, TB)
        $this->assertMatchesRegularExpression('/\d+(\.\d+)?\s+(B|KB|MB|GB|TB)/', $freeSpace);
    }

    /**
     * Test health check response structure consistency
     */
    public function test_health_check_response_structure_consistency(): void
    {
        $service = new HealthCheckService();
        $result = $service->check();

        // Check main structure
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('environment', $result);
        $this->assertArrayHasKey('checks', $result);

        // Check each subsystem has consistent structure
        foreach ($result['checks'] as $checkName => $check) {
            $this->assertArrayHasKey('status', $check, "Check '{$checkName}' missing status");
            $this->assertArrayHasKey('message', $check, "Check '{$checkName}' missing message");
            $this->assertContains($check['status'], ['ok', 'warning', 'error'], "Invalid status for '{$checkName}'");
            $this->assertIsString($check['message'], "Message should be string for '{$checkName}'");
        }

        // Timestamp should be valid ISO string
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $result['timestamp']);
    }

    /**
     * Test system performance under load
     */
    public function test_system_performance_under_load(): void
    {
        $startTime = microtime(true);
        
        // Make multiple concurrent requests to health endpoints
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->getJson('/api/v1/health');
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // All responses should be successful
        foreach ($responses as $response) {
            $response->assertStatus(200)
                    ->assertJson(['status' => 'ok']);
        }

        // Should complete within reasonable time (2 seconds for 5 requests)
        $this->assertLessThan(2000, $totalTime, 'Health checks took too long under load');
    }

    /**
     * Test health endpoints availability without authentication
     */
    public function test_health_endpoints_available_without_authentication(): void
    {
        // Basic health check should be publicly accessible
        $response = $this->getJson('/api/v1/health');
        $response->assertStatus(200);

        // Detailed health check should also be publicly accessible
        $response = $this->getJson('/api/v1/health/detailed');
        $response->assertStatus(200);

        // Test endpoint should be publicly accessible
        $response = $this->getJson('/api/v1/test');
        $response->assertStatus(200);
    }

    /**
     * Test environment information is properly exposed
     */
    public function test_environment_information_is_properly_exposed(): void
    {
        $response = $this->getJson('/api/v1/health');
        $data = $response->json();

        $this->assertArrayHasKey('environment', $data);
        $this->assertContains($data['environment'], ['testing', 'local', 'development', 'production']);

        // Check that environment matches the current app environment
        $this->assertEquals(config('app.env'), $data['environment']);
    }

    /**
     * Test health check service handles missing database tables gracefully
     */
    public function test_health_check_handles_missing_database_tables(): void
    {
        // Mock a scenario where users table doesn't exist
        DB::shouldReceive('table')
          ->with('users')
          ->andThrow(new \Exception('Table users doesn\'t exist'));

        $service = new HealthCheckService();
        $result = $service->check();

        $this->assertEquals('error', $result['status']);
        $dbCheck = $result['checks']['database'];
        $this->assertEquals('error', $dbCheck['status']);
        $this->assertEquals('Database connection failed', $dbCheck['message']);
    }

    /**
     * Test cache health check with different cache drivers
     */
    public function test_cache_health_check_with_different_drivers(): void
    {
        // Test with array driver (default in testing)
        config(['cache.default' => 'array']);
        
        $service = new HealthCheckService();
        $result = $service->check();

        $cacheCheck = $result['checks']['cache'];
        $this->assertEquals('ok', $cacheCheck['status']);
        $this->assertEquals('array', $cacheCheck['driver']);
    }

    /**
     * Test detailed health check includes version information
     */
    public function test_detailed_health_check_includes_version_information(): void
    {
        config(['app.version' => '2.1.0']);
        
        $response = $this->getJson('/api/v1/health/detailed');
        
        $response->assertStatus(200)
                ->assertJson([
                    'version' => '2.1.0'
                ]);
    }
}