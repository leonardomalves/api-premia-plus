<?php

namespace Database\Seeders;

use App\Services\Core\HttpClient;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CreateUsersSeed extends Seeder
{
    private $baseUrl = 'http://localhost:8000/api/v1';
    private $adminToken = null;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando criação de usuários de teste via API...');

        // Fazer login do admin para obter token
        $this->loginAdmin();
        
        // Criar usuários de teste via API
        $this->createTestUsers();

        $this->command->info('✅ Usuários de teste criados com sucesso via API!');
    }

    /**
     * Fazer login do admin para obter token de acesso
     */
    private function loginAdmin(): void
    {
        $this->command->info('🔐 Fazendo login do admin para criar usuários...');

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
            $this->command->warn('⚠️ Certifique-se de que o admin foi criado antes de executar esta seed');
            return;
        }
    }

    /**
     * Criar usuários de teste via API
     */
    private function createTestUsers(): void
    {
        if (!$this->adminToken) {
            $this->command->error('❌ Token do admin não disponível. Não é possível criar usuários.');
            return;
        }

        $roles = ['user', 'moderator', 'support', 'finance'];
        $statuses = ['active', 'inactive', 'suspended'];

        $this->command->info('👥 Criando usuários de teste...');

        for ($i = 1; $i <= 15; $i++) {
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
                
                // Atualizar role e status via API
                $this->updateUserRoleAndStatus($response->content->user->uuid ?? null, $role, $status);
            } else {
                $this->command->error("❌ Erro ao criar usuário {$i}: " . ($response->content->message ?? 'Erro desconhecido'));
                $this->command->error("Status: {$response->status}");
                
                // Log apenas em caso de erro para não poluir a saída
                if ($response->status >= 400) {
                    $this->command->error("Response: " . json_encode($response->content));
                }
            }

            // Pequena pausa para não sobrecarregar a API
            usleep(100000); // 0.1 segundo
        }

        $this->command->info('📊 Resumo da criação de usuários concluído!');
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

        if ($response->status == 200) {
            $this->command->line("   🔧 Role: {$role} | Status: {$status}");
        } else {
            $this->command->warn("⚠️ Não foi possível atualizar role/status do usuário {$userUuid}");
            $this->command->warn("Status: {$response->status} - " . ($response->content->message ?? 'Erro desconhecido'));
        }
    }
}