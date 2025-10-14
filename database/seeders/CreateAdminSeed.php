<?php

namespace Database\Seeders;

use App\Services\Core\HttpClient;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CreateAdminSeed extends Seeder
{
    private $baseUrl = 'http://localhost:8000/api/v1';
    private $adminToken = null;

    /**
     * Verificar se o servidor Laravel está rodando
     */
    private function checkServerRunning(): bool
    {
        $this->command->info('🔍 Verificando se o servidor Laravel está rodando...');
        
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 3,
                    'method' => 'GET',
                ]
            ]);
            
            $response = @file_get_contents('http://localhost:8000', false, $context);
            
            if ($response !== false) {
                $this->command->info('✅ Servidor Laravel está rodando na porta 8000');
                return true;
            } else {
                $this->command->error('❌ Servidor Laravel não está respondendo na porta 8000');
                return false;
            }
        } catch (\Exception $e) {
            $this->command->error("❌ Erro ao verificar servidor: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando criação dos usuários administradores via API...');

        // Verificar se o servidor está rodando
        if (!$this->checkServerRunning()) {
            $this->command->error('❌ Servidor Laravel não está rodando.');
            $this->command->warn('💡 Execute em outro terminal: php artisan serve');
            $this->command->warn('💡 Ou verifique se o servidor está rodando na porta 8000');
            return;
        }

        // Verificar se a API está disponível
        if (!$this->checkApiHealth()) {
            $this->command->error('❌ API não está disponível. Verifique as rotas da API.');
            return;
        }

        // Criar múltiplos administradores
        $this->createAdminUsers();
        
        // Fazer login do admin principal para obter token
        $this->loginMainAdmin();
        
        // Verificar configuração dos admins
        $this->checkAdminsConfiguration();

        $this->command->info('✅ Administradores criados com sucesso via API!');
    }

    /**
     * Verificar se a API está disponível
     */
    private function checkApiHealth(): bool
    {
        $this->command->info('🔍 Verificando disponibilidade da API...');
        
        try {
            $response = (new HttpClient())->apiRequest("{$this->baseUrl}/health", [], [], 'GET');
            
            if ($response->status == 200) {
                $this->command->info('✅ API está disponível e respondendo');
                return true;
            } else {
                $this->command->warn("⚠️ API respondeu com status: {$response->status}");
                return false;
            }
        } catch (\Exception $e) {
            $this->command->error("❌ Erro ao conectar com a API: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Criar múltiplos usuários admin via API
     */
    private function createAdminUsers(): void
    {
        $admins = $this->getAdminProfiles();
        $successCount = 0;
        $errorCount = 0;

        foreach ($admins as $index => $admin) {
            $this->command->info("👤 Criando admin: {$admin['name']}...");

            try {
                $response = (new HttpClient())->apiRequest("{$this->baseUrl}/register", $admin, [], 'POST');

                if ($response->status == 200 || $response->status == 201) {
                    $this->command->info("✅ {$admin['name']} criado com sucesso!");
                    $successCount++;
                    
                    // Armazenar token do primeiro admin (principal) para usar nos testes
                    if ($index === 0) {
                        $this->adminToken = $response->content->access_token ?? null;
                    }
                } else {
                    $this->handleAdminCreationError($admin, $response);
                    $errorCount++;
                }
            } catch (\Exception $e) {
                $this->command->error("❌ Exceção ao criar {$admin['name']}: {$e->getMessage()}");
                $errorCount++;
            }

            // Pequena pausa entre criações
            usleep(200000); // 0.2 segundo
        }

        // Resumo da criação
        $total = count($admins);
        $this->command->info("📊 Resumo: {$successCount}/{$total} admins criados com sucesso");
        
        if ($errorCount > 0) {
            $this->command->warn("⚠️ {$errorCount} admin(s) falharam na criação");
        }
    }

    /**
     * Tratar erro na criação de admin
     */
    private function handleAdminCreationError(array $admin, object $response): void
    {
        $this->command->error("❌ Erro ao criar {$admin['name']}:");
        
        if ($response->status === 0) {
            $this->command->error('  • Problema de conectividade - verifique se a API está rodando');
            $this->command->warn('  • Execute: php artisan serve');
        } elseif ($response->status === 422) {
            $this->command->error('  • Dados de validação inválidos');
            if (isset($response->content->errors)) {
                foreach ($response->content->errors as $field => $errors) {
                    $this->command->error("  • {$field}: " . implode(', ', $errors));
                }
            }
        } elseif ($response->status === 409) {
            $this->command->warn('  • Admin já existe (email ou username duplicado)');
        } else {
            $this->command->error("  • Status: {$response->status}");
            $this->command->error('  • Mensagem: ' . ($response->content->message ?? 'Erro desconhecido'));
        }
    }

    /**
     * Definir perfis dos administradores
     */
    private function getAdminProfiles(): array
    {
        return [
            [
                'name' => 'Administrador Principal',
                'email' => 'admin@premiaplus.com',
                'username' => 'admin',
                'password' => 'password',
                'password_confirmation' => 'password',
                'phone' => '11999999999',
                'role' => 'admin',
            ],
            [
                'name' => 'Super Administrador',
                'email' => 'superadmin@premiaplus.com',
                'username' => 'superadmin',
                'password' => 'password',
                'password_confirmation' => 'password',
                'phone' => '11998888888',
                'role' => 'admin',
            ],
            [
                'name' => 'Administrador Financeiro',
                'email' => 'admin.financeiro@premiaplus.com',
                'username' => 'admin_financeiro',
                'password' => 'password',
                'password_confirmation' => 'password',
                'phone' => '11997777777',
                'role' => 'admin',
            ],
            [
                'name' => 'Administrador de Sistema',
                'email' => 'admin.sistema@premiaplus.com',
                'username' => 'admin_sistema',
                'password' => 'password',
                'password_confirmation' => 'password',
                'phone' => '11996666666',
                'role' => 'admin',
            ],
            [
                'name' => 'Administrador de Suporte',
                'email' => 'admin.suporte@premiaplus.com',
                'username' => 'admin_suporte',
                'password' => 'password',
                'password_confirmation' => 'password',
                'phone' => '11995555555',
                'role' => 'admin',
            ]
        ];
    }

    /**
     * Fazer login do admin principal
     */
    private function loginMainAdmin(): void
    {
        if (!$this->adminToken) {
            $this->command->warn('⚠️ Admin principal pode não ter sido criado. Tentando login manual...');
        }

        $this->command->info('🔐 Fazendo login do admin principal...');

        $data = [
            'email' => 'admin@premiaplus.com',
            'password' => 'password',
        ];

        try {
            $response = (new HttpClient())->apiRequest("{$this->baseUrl}/login", $data, [], 'POST');

            if ($response->status == 200) {
                $this->adminToken = $response->content->access_token ?? null;
                $this->command->info('✅ Login do admin principal realizado!');
            } elseif ($response->status === 0) {
                $this->command->error('❌ Problema de conectividade no login - API não está respondendo');
            } elseif ($response->status === 401) {
                $this->command->error('❌ Credenciais inválidas - admin pode não ter sido criado corretamente');
            } else {
                $this->command->error("❌ Erro no login (Status: {$response->status}): " . ($response->content->message ?? 'Erro desconhecido'));
            }
        } catch (\Exception $e) {
            $this->command->error("❌ Exceção no login: {$e->getMessage()}");
        }
    }

    /**
     * Verificar configuração dos admins
     */
    private function checkAdminsConfiguration(): void
    {
        if (!$this->adminToken) {
            $this->command->warn('⚠️ Token do admin principal não disponível');
            return;
        }

        $this->command->info('🔍 Verificando configuração dos administradores...');

        // Verificar admin principal
        $this->checkSingleAdminRole();

        // Testar login de todos os admins
        $this->testAllAdminsLogin();

        // Testar endpoints básicos com privilégios de admin
        $this->testAdminEndpoints();
    }

    /**
     * Verificar role do admin principal
     */
    private function checkSingleAdminRole(): void
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->adminToken,
        ];

        $response = (new HttpClient())->apiRequest("{$this->baseUrl}/me", [], $headers, 'GET');

        if ($response->status == 200) {
            $userRole = $response->content->user->role ?? 'unknown';
            $userName = $response->content->user->name ?? 'unknown';
            $this->command->info("👤 Admin principal: {$userName} | Role: {$userRole}");
            
            if ($userRole !== 'admin') {
                $this->command->warn('⚠️ Admin principal não tem role de administrador!');
            } else {
                $this->command->info('✅ Admin principal configurado corretamente!');
            }
        } else {
            $this->command->error('❌ Erro ao verificar role do admin principal');
        }
    }

    /**
     * Testar login de todos os admins criados
     */
    private function testAllAdminsLogin(): void
    {
        $this->command->info('🔐 Testando login de todos os administradores...');

        $admins = $this->getAdminProfiles();
        $successfulLogins = 0;

        foreach ($admins as $admin) {
            $loginData = [
                'email' => $admin['email'],
                'password' => $admin['password'],
            ];

            $response = (new HttpClient())->apiRequest("{$this->baseUrl}/login", $loginData, [], 'POST');

            if ($response->status == 200) {
                $successfulLogins++;
                $this->command->line("  ✅ {$admin['name']} - Login OK");
            } else {
                $this->command->line("  ❌ {$admin['name']} - Login Falhou");
            }

            // Pequena pausa entre testes
            usleep(150000); // 0.15 segundo
        }

        $totalAdmins = count($admins);
        $this->command->info("📊 Resultado: {$successfulLogins}/{$totalAdmins} administradores podem fazer login");
    }

    /**
     * Testar endpoints básicos da API
     */
    private function testAdminEndpoints(): void
    {
        $this->command->info('🧪 Testando endpoints básicos da API...');

        $endpoints = [
            ['GET', '/health', 'Health check'],
            ['GET', '/test', 'Test endpoint'],
        ];

        foreach ($endpoints as [$method, $endpoint, $description]) {
            $response = (new HttpClient())->apiRequest("{$this->baseUrl}{$endpoint}", [], [], $method);
            if ($response->status == 200) {
                $this->command->line("  ✅ {$description} OK");
            } else {
                $this->command->line("  ❌ {$description} Falhou (Status: {$response->status})");
            }
        }

        // Testar endpoint autenticado
        if ($this->adminToken) {
            $headers = ['Authorization' => 'Bearer ' . $this->adminToken];
            $response = (new HttpClient())->apiRequest("{$this->baseUrl}/me", [], $headers, 'GET');
            
            if ($response->status == 200) {
                $this->command->line('  ✅ Admin profile endpoint OK');
            } else {
                $this->command->line("  ❌ Admin profile endpoint Falhou (Status: {$response->status})");
            }
        }

        $this->command->info('🎯 Testes de endpoints concluídos!');
    }
}