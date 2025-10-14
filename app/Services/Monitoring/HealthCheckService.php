<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthCheckService
{
    /**
     * Perform comprehensive health check
     */
    public function check(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $overall = collect($checks)->every(fn($check) => $check['status'] === 'ok');

        $result = [
            'status' => $overall ? 'ok' : 'error',
            'timestamp' => now()->toISOString(),
            'version' => config('app.version', '1.0.0'),
            'environment' => app()->environment(),
            'checks' => $checks
        ];

        // Log se houver problemas
        if (!$overall) {
            Log::warning('Health check failed', $result);
        }

        return $result;
    }

    /**
     * Check database connectivity
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $userCount = DB::table('users')->count();
            
            return [
                'status' => 'ok',
                'message' => 'Database connected',
                'details' => ['user_count' => $userCount]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check cache system
     */
    private function checkCache(): array
    {
        try {
            $key = 'health_check_' . time();
            Cache::put($key, 'test', 60);
            $value = Cache::get($key);
            Cache::forget($key);
            
            if ($value === 'test') {
                return [
                    'status' => 'ok',
                    'message' => 'Cache working',
                    'driver' => config('cache.default')
                ];
            }
            
            throw new \Exception('Cache value mismatch');
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check storage permissions
     */
    private function checkStorage(): array
    {
        try {
            $paths = [
                storage_path('logs'),
                storage_path('framework/cache'),
                storage_path('framework/sessions'),
                storage_path('framework/views')
            ];

            foreach ($paths as $path) {
                if (!is_writable($path)) {
                    throw new \Exception("Path not writable: {$path}");
                }
            }

            return [
                'status' => 'ok',
                'message' => 'Storage writable',
                'free_space' => $this->formatBytes(disk_free_space(storage_path()))
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Storage check failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check queue system
     */
    private function checkQueue(): array
    {
        try {
            $driver = config('queue.default');
            
            // Verificações básicas por driver
            $details = ['driver' => $driver];
            
            if ($driver === 'database') {
                $pendingJobs = DB::table('jobs')->count();
                $failedJobs = DB::table('failed_jobs')->count();
                
                $details['pending_jobs'] = $pendingJobs;
                $details['failed_jobs'] = $failedJobs;
                
                // Alerta se muitos jobs falharam
                if ($failedJobs > 10) {
                    return [
                        'status' => 'warning',
                        'message' => 'High number of failed jobs',
                        'details' => $details
                    ];
                }
            }

            return [
                'status' => 'ok',
                'message' => 'Queue system operational',
                'details' => $details
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Queue check failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}