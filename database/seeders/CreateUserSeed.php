<?php

namespace Database\Seeders;

use App\Services\Core\HttpClient;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CreateUserSeed extends Seeder
{
    private $baseUrl = 'http://localhost:8000/api/v1';
    private $adminToken = null;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando criação de usuários via API...');

        // Criar usuário admin primeiro
        $this->createAdminUser();
        
        // Fazer login do admin para obter token
        $this->loginAdmin();
        
        // Criar usuários de teste via API
        $this->createTestUsers();
        
        // Testar endpoints da API
        $this->testApiEndpoints();

        $this->command->info('✅ Seeders executados com sucesso via API!');
    }

    /**
     * Criar usuário admin via API
     */
    private function createAdminUser(): void
    {
        $this->command->info('👤 Criando usuário admin...');

        $data = [
            'name' => 'Administrador Principal',
            'email' => 'admin@premiaplus.com',
            'username' => 'admin',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '11999999999',
            'role' => 'admin',
        ];

        $response = (new HttpClient())->apiRequest("{$this->baseUrl}/register", $data, [], 'POST');

        if ($response->status == 200 || $response->status == 201) {
            $this->command->info('✅ Admin criado com sucesso!');
            $this->adminToken = $response->content->access_token ?? null;
            
            // Verificar se o admin tem a role correta
            $this->command->info('🔍 Verificando role do admin...');
            $this->checkAdminRole();
        } else {
            $this->command->error('❌ Erro ao criar admin: ' . ($response->content->message ?? 'Erro desconhecido'));
            $this->command->error('Status: ' . $response->status);
            $this->command->error('Response: ' . json_encode($response->content));
        }
    }

    /**
     * Fazer login do admin
     */
    private function loginAdmin(): void
    {
        $this->command->info('🔐 Fazendo login do admin...');

        $data = [
            'email' => 'admin@premiaplus.com',
            'password' => 'password',
        ];

        $response = (new HttpClient())->apiRequest("{$this->baseUrl}/login", $data, [], 'POST');

        if ($response->status == 200) {
            $this->adminToken = $response->content->access_token ?? null;
            $this->command->info('✅ Login do admin realizado!');
        } else {
            $this->command->error('❌ Erro no login do admin: ' . ($response->content->message ?? 'Erro desconhecido'));
        }
    }

    /**
     * Criar usuários de teste via API
     */
    private function createTestUsers(): void
    {
        $roles = ['user', 'moderator', 'support', 'finance'];
        $statuses = ['active', 'inactive', 'suspended'];

        $this->command->info('👥 Criando usuários de teste...');

        for ($i = 1; $i <= 50; $i++) {
            $role = $roles[array_rand($roles)];
            $status = $statuses[array_rand($statuses)];

            // Definir sponsor aleatório
            $sponsor = null;
            if ($i > 1) {
                $sponsor = "user" . rand(1, min($i - 1, 10));
            }

            $userData = [
                'name' => "Usuário {$role} {$i}",
                'email' => "user{$i}@premiaplus.com",
                'username' => "user{$i}",
                'password' => 'password',
                'password_confirmation' => 'password',
                'phone' => '119' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
            ];

            // Adicionar sponsor se existir
            if ($sponsor) {
                $userData['sponsor'] = $sponsor;
            }

            $response = (new HttpClient())->apiRequest("{$this->baseUrl}/register", $userData, [], 'POST');

            if ($response->status == 200 || $response->status == 201) {
                $this->command->info("✅ Usuário {$i} criado: user{$i}@premiaplus.com");
                
                // Atualizar role e status via API (se admin estiver logado)
                if ($this->adminToken) {
                    $this->updateUserRoleAndStatus($response->content->user->uuid ?? null, $role, $status);
                }
            } else {
                $this->command->error("❌ Erro ao criar usuário {$i}: " . ($response->content->message ?? 'Erro desconhecido'));
                $this->command->error("Status: {$response->status}");
                $this->command->error("Response: " . json_encode($response->content));
            }

            // Pequena pausa para não sobrecarregar
            usleep(100000); // 0.1 segundo
        }
    }

    /**
     * Atualizar role e status do usuário via API
     */
    private function updateUserRoleAndStatus(?string $userUuid, string $role, string $status): void
    {
        if (!$userUuid) {
            $this->command->warn("⚠️ UUID do usuário não disponível");
            return;
        }

        $headers = [
            'Authorization' => 'Bearer ' . $this->adminToken,
            'Accept' => 'application/json',
        ];

        $data = [
            'role' => $role,
            'status' => $status,
        ];

        $response = (new HttpClient())->apiRequest("{$this->baseUrl}/administrator/users/{$userUuid}", $data, $headers, 'PUT');

        if ($response->status != 200) {
            $this->command->warn("⚠️ Não foi possível atualizar role/status do usuário {$userUuid}");
            $this->command->warn("Status: {$response->status} - " . ($response->content->message ?? 'Erro desconhecido'));
        }
    }

    /**
     * Verificar role do admin
     */
    private function checkAdminRole(): void
    {
        if (!$this->adminToken) {
            $this->command->warn('⚠️ Token do admin não disponível');
            return;
        }

        $headers = [
            'Authorization' => 'Bearer ' . $this->adminToken,
        ];

        $response = (new HttpClient())->apiRequest("{$this->baseUrl}/me", [], $headers, 'GET');

        if ($response->status == 200) {
            $userRole = $response->content->user->role ?? 'unknown';
            $this->command->info("👤 Role do admin: {$userRole}");
            
            if ($userRole !== 'admin') {
                $this->command->warn('⚠️ Admin não tem role de administrador!');
            }
        } else {
            $this->command->error('❌ Erro ao verificar role do admin');
        }
    }

    /**
     * Testar endpoints da API
     */
    private function testApiEndpoints(): void
    {
        $this->command->info('🧪 Testando endpoints da API...');

        // Testar health check
        $response = (new HttpClient())->apiRequest("{$this->baseUrl}/health", [], [], 'GET');
        if ($response->status == 200) {
            $this->command->info('✅ Health check OK');
        }

        // Testar endpoint de teste
        $response = (new HttpClient())->apiRequest("{$this->baseUrl}/test", [], [], 'GET');
        if ($response->status == 200) {
            $this->command->info('✅ Test endpoint OK');
        }

        // Testar dados do admin
        if ($this->adminToken) {
            $headers = [
                'Authorization' => 'Bearer ' . $this->adminToken,
            ];
            
            $response = (new HttpClient())->apiRequest("{$this->baseUrl}/me", [], $headers, 'GET');
            if ($response->status == 200) {
                $this->command->info('✅ Admin profile OK');
            }
        }
    }
}