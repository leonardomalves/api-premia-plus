<?php

namespace Database\Seeders;

use App\Services\Core\HttpClient;
use Illuminate\Database\Seeder;

class AddToCartSeed extends Seeder
{
    private $baseUrl = 'http://localhost:8000/api/v1';

    private $availablePlans = [];

    private $userTokens = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🛒 Iniciando simulação de adição ao carrinho...');

        // Verificar se a API está disponível
        if (! $this->checkApiHealth()) {
            $this->command->error('❌ API não está disponível. Certifique-se de que o servidor está rodando.');

            return;
        }

        // Diagnóstico adicional - verificar rotas disponíveis
        $this->checkAvailableRoutes();

        // Obter lista de planos disponíveis
        $this->fetchAvailablePlans();

        if (empty($this->availablePlans)) {
            $this->command->error('❌ Nenhum plano encontrado.');
            $this->command->warn('💡 Possíveis soluções:');
            $this->command->warn('   1. Execute: php artisan db:seed --class=PlanSeed');
            $this->command->warn('   2. Verifique se as rotas da API estão corretas');
            $this->command->warn('   3. Verifique se há planos cadastrados no banco');

            return;
        }

        // Simular compras para todos os usuários
        $this->simulateUserPurchases();

        $this->command->info('✅ Simulação de carrinho concluída!');
    }

    /**
     * Verificar se a API está disponível
     */
    private function checkApiHealth(): bool
    {
        $this->command->info('🔍 Verificando disponibilidade da API...');

        try {
            $response = (new HttpClient)->apiRequest("{$this->baseUrl}/health", [], [], 'GET');

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
     * Obter lista de planos disponíveis
     */
    private function fetchAvailablePlans(): void
    {
        $this->command->info('📋 Obtendo lista de planos disponíveis...');

        // Usar autenticação de usuário para acessar planos via endpoint customer
        $this->command->line('🔐 Autenticando usuário para acessar planos...');
        $userToken = $this->authenticateUser('user1@premiaplus.com', 'password');

        if ($userToken) {
            $headers = [
                'Authorization' => 'Bearer '.$userToken,
                'Accept' => 'application/json',
            ];

            // Usar endpoint correto do customer
            $response = $this->tryFetchPlansFromCustomerEndpoint($headers);

            if ($response && $response->status == 200) {
                $this->processPlanResponse($response);

                return;
            }
        }

        // Se falhar com user, tentar com admin
        $this->command->line('🔐 Tentando com admin como fallback...');
        $adminToken = $this->getAdminToken();

        if ($adminToken) {
            $this->tryAlternativePlanEndpoints($adminToken);
        }
    }

    /**
     * Tentar buscar planos do endpoint do customer
     */
    private function tryFetchPlansFromCustomerEndpoint(array $headers): ?object
    {
        try {
            $response = (new HttpClient)->apiRequest("{$this->baseUrl}/plans", [], $headers, 'GET');

            $this->command->line("  Status da resposta /plans: {$response->status}");

            if ($response->status !== 200) {
                $this->command->line('  Conteúdo da resposta: '.json_encode($response->content));
            }

            return $response;
        } catch (\Exception $e) {
            $this->command->error("  Exceção: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Tentar buscar planos com headers opcionais
     */
    private function tryFetchPlans(array $headers = []): ?object
    {
        try {
            $response = (new HttpClient)->apiRequest("{$this->baseUrl}/plans", [], $headers, 'GET');

            $this->command->line("  Status da resposta: {$response->status}");

            if ($response->status !== 200) {
                $this->command->line('  Conteúdo da resposta: '.json_encode($response->content));
            }

            return $response;
        } catch (\Exception $e) {
            $this->command->error("  Exceção: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Processar resposta dos planos
     */
    private function processPlanResponse(object $response): void
    {
        // Processar estrutura de resposta dos planos

        // Verificar diferentes estruturas possíveis da resposta
        $plans = null;

        if (isset($response->content->data->plans)) {
            $plans = $response->content->data->plans;
        } elseif (isset($response->content->data)) {
            $plans = $response->content->data;
        } elseif (isset($response->content->plans)) {
            $plans = $response->content->plans;
        } elseif (isset($response->content) && is_array($response->content)) {
            $plans = $response->content;
        } elseif (isset($response->content) && is_object($response->content)) {
            // Converter object para array e pegar todos os arrays internos
            $contentArray = (array) $response->content;
            foreach ($contentArray as $key => $value) {
                if (is_array($value) && ! empty($value)) {
                    // Verificar se é um array de planos
                    $firstItem = reset($value);
                    if (is_object($firstItem) && (isset($firstItem->name) || isset($firstItem->title))) {
                        $plans = $value;
                        break;
                    }
                }
            }
        }

        if ($plans && (is_array($plans) || is_object($plans))) {
            $this->availablePlans = is_array($plans) ? $plans : [$plans];
            $planCount = count($this->availablePlans);
            $this->command->info("✅ {$planCount} planos encontrados");

            // Mostrar planos disponíveis
            foreach ($this->availablePlans as $plan) {
                $planObj = is_object($plan) ? $plan : (object) $plan;
                $name = $planObj->name ?? $planObj->title ?? 'Nome não definido';
                $price = $planObj->price ?? $planObj->value ?? 'Preço não definido';
                $id = $planObj->id ?? $planObj->uuid ?? 'ID não encontrado';
                $this->command->line("  📦 {$name} (ID: {$id}) - R$ {$price}");
            }
        } else {
            $this->command->warn('⚠️ Estrutura de resposta não reconhecida');
            $this->command->line('Resposta completa: '.json_encode($response->content));
        }
    }

    /**
     * Obter token de admin para autenticação
     */
    private function getAdminToken(): ?string
    {
        $loginData = [
            'email' => 'admin@premiaplus.com',
            'password' => 'password',
        ];

        try {
            $response = (new HttpClient)->apiRequest("{$this->baseUrl}/login", $loginData, [], 'POST');

            if ($response->status == 200) {
                return $response->content->access_token ?? null;
            } else {
                $this->command->line("  ❌ Falha no login admin (Status: {$response->status})");

                return null;
            }
        } catch (\Exception $e) {
            $this->command->line("  ❌ Erro no login admin: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Tentar endpoints alternativos para planos
     */
    private function tryAlternativePlanEndpoints(?string $adminToken): void
    {
        $this->command->line('🔍 Tentando endpoints alternativos...');

        // Endpoints alternativos como estava antes
        $alternativeEndpoints = [
            '/plans/list',
            '/admin/plans',
            '/administrator/plans',
            '/packages',
            '/products',
        ];

        $headers = [];
        if ($adminToken) {
            $headers = [
                'Authorization' => 'Bearer '.$adminToken,
                'Accept' => 'application/json',
            ];
        }

        foreach ($alternativeEndpoints as $endpoint) {
            $this->command->line("  Testando: {$endpoint}");

            try {
                $response = (new HttpClient)->apiRequest("{$this->baseUrl}{$endpoint}", [], $headers, 'GET');

                if ($response->status == 200) {
                    $this->command->info("  ✅ Endpoint funcional encontrado: {$endpoint}");
                    $this->processPlanResponse($response);

                    return;
                } else {
                    $this->command->line("    Status: {$response->status}");
                }
            } catch (\Exception $e) {
                $this->command->line("    Erro: {$e->getMessage()}");
            }
        }

        $this->command->error('❌ Nenhum endpoint de planos funcional encontrado');
    }

    /**
     * Verificar rotas disponíveis da API
     */
    private function checkAvailableRoutes(): void
    {
        $this->command->info('🔍 Verificando rotas disponíveis da API...');

        $testRoutes = [
            '/health' => 'Health Check',
            '/test' => 'Test Endpoint',
            '/plans' => 'Planos (Público)',
            // '/plans' => 'Planos (Customer)',
            '/customer/cart/add' => 'Carrinho (Customer)',
            '/login' => 'Login',
            '/register' => 'Registro',
        ];

        foreach ($testRoutes as $route => $description) {
            try {
                $response = (new HttpClient)->apiRequest("{$this->baseUrl}{$route}", [], [], 'GET');
                $status = $response->status ?? 'N/A';

                if ($status == 200) {
                    $this->command->line("  ✅ {$description} ({$route}) - OK");
                } elseif ($status == 405) {
                    $this->command->line("  🔄 {$description} ({$route}) - Método não permitido (rota existe)");
                } elseif ($status == 404) {
                    $this->command->line("  ❌ {$description} ({$route}) - Não encontrado");
                } else {
                    $this->command->line("  ⚠️ {$description} ({$route}) - Status: {$status}");
                }
            } catch (\Exception $e) {
                $this->command->line("  ❌ {$description} ({$route}) - Erro: {$e->getMessage()}");
            }
        }
    }

    /**
     * Simular compras de usuários
     */
    private function simulateUserPurchases(): void
    {
        $this->command->info('🎭 Iniciando simulação de compras de usuários...');

        // Simular para usuários de teste (assumindo que existem user1@premiaplus.com até user50@premiaplus.com)
        $totalUsers = 50;
        $successfulPurchases = 0;
        $failedAuthentications = 0;
        $failedPurchases = 0;

        for ($i = 1; $i <= $totalUsers; $i++) {
            $userEmail = "user{$i}@premiaplus.com";

            $this->command->info("👤 Processando usuário: {$userEmail}");

            // 1. Autenticar usuário
            $userToken = $this->authenticateUser($userEmail, 'password');

            if (! $userToken) {
                $this->command->warn("  ⚠️ Falha na autenticação do usuário {$i}");
                $failedAuthentications++;

                continue;
            }

            // 2. Visitar lista de pacotes (simular navegação)
            $this->visitPlansList($userToken, $userEmail);

            // 3. Adicionar pacote ao carrinho (chance de 70% de comprar)
            $shouldPurchase = rand(1, 100) <= 70; // 70% de chance

            if ($shouldPurchase) {
                $success = $this->addPlanToCart($userToken, $userEmail);

                if ($success) {
                    $successfulPurchases++;
                } else {
                    $failedPurchases++;
                }
            } else {
                $this->command->line("  🚶 Usuário {$i} visitou mas não comprou");
            }

            // Pausa para simular comportamento real
            usleep(rand(500000, 1000000)); // 0.5 a 1 segundo
        }

        // Resumo da simulação
        $this->showPurchasesSummary($totalUsers, $successfulPurchases, $failedAuthentications, $failedPurchases);
    }

    /**
     * Autenticar usuário
     */
    private function authenticateUser(string $email, string $password): ?string
    {
        $loginData = [
            'email' => $email,
            'password' => $password,
        ];

        try {
            $response = (new HttpClient)->apiRequest("{$this->baseUrl}/login", $loginData, [], 'POST');

            if ($response->status == 200) {
                $token = $response->content->access_token ?? null;
                $this->command->line('  ✅ Autenticado com sucesso');

                return $token;
            } else {
                $this->command->line("  ❌ Falha na autenticação (Status: {$response->status})");

                return null;
            }
        } catch (\Exception $e) {
            $this->command->line("  ❌ Erro na autenticação: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Simular visita à lista de planos
     */
    private function visitPlansList(string $token, string $userEmail): void
    {
        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ];

        try {
            // Usar endpoint correto do customer conforme as rotas
            $response = (new HttpClient)->apiRequest("{$this->baseUrl}/plans", [], $headers, 'GET');

            if ($response->status == 200) {
                $this->command->line('  👀 Visitou a lista de planos');
            } else {
                $this->command->line("  ⚠️ Erro ao visitar planos (Status: {$response->status})");
            }
        } catch (\Exception $e) {
            $this->command->line("  ⚠️ Erro ao visitar planos: {$e->getMessage()}");
        }

        // Simular tempo de navegação
        usleep(rand(200000, 800000)); // 0.2 a 0.8 segundos
    }

    /**
     * Adicionar plano aleatório ao carrinho
     */
    private function addPlanToCart(string $token, string $userEmail): bool
    {
        if (empty($this->availablePlans)) {
            return false;
        }

        // Escolher um plano aleatório
        $randomPlan = $this->availablePlans[array_rand($this->availablePlans)];

        // Se o plano selecionado é um objeto que contém um array de planos, pegar um plano do array
        if (is_object($randomPlan) && isset($randomPlan->plans) && is_array($randomPlan->plans)) {
            $randomPlan = $randomPlan->plans[array_rand($randomPlan->plans)];
        }

        $planObj = is_object($randomPlan) ? $randomPlan : (object) $randomPlan;

        // Buscar ID do plano de diferentes formas possíveis
        $planId = $planObj->id ?? $planObj->uuid ?? $planObj->plan_id ?? null;
        $planName = $planObj->name ?? $planObj->title ?? 'Plano sem nome';

        if (! $planId) {
            $this->command->line('  ❌ ID do plano não encontrado');
            $this->command->line('  Estrutura do plano: '.json_encode($planObj));

            return false;
        }

        $headers = [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ];

        $cartData = [
            'plan_uuid' => $planObj->uuid ?? $planId,
            'quantity' => 1,
        ];

        try {
            // Usar o endpoint correto do carrinho
            $response = (new HttpClient)->apiRequest("{$this->baseUrl}/customer/cart/add", $cartData, $headers, 'POST');

            if ($response->status == 200 || $response->status == 201) {
                $this->command->line("  🛒 Adicionou '{$planName}' ao carrinho");

                return true;
            } else {
                $this->command->line("  ❌ Erro ao adicionar ao carrinho (Status: {$response->status})");

                // Log detalhado do erro se necessário
                if (isset($response->content->message)) {
                    $this->command->line("     Mensagem: {$response->content->message}");
                }

                return false;
            }

        } catch (\Exception $e) {
            $this->command->line("  ❌ Erro ao adicionar ao carrinho: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Mostrar resumo das simulações
     */
    private function showPurchasesSummary(int $total, int $successful, int $authFailed, int $purchaseFailed): void
    {
        $this->command->info('');
        $this->command->info('📊 RESUMO DA SIMULAÇÃO DE CARRINHO');
        $this->command->info('═══════════════════════════════════');
        $this->command->info("👥 Total de usuários processados: {$total}");
        $this->command->info("✅ Compras bem-sucedidas: {$successful}");
        $this->command->info("🔐 Falhas de autenticação: {$authFailed}");
        $this->command->info("🛒 Falhas ao adicionar ao carrinho: {$purchaseFailed}");

        $visitedButNotPurchased = $total - $successful - $authFailed - $purchaseFailed;
        $this->command->info("🚶 Visitaram mas não compraram: {$visitedButNotPurchased}");

        if ($total > 0) {
            $successRate = round(($successful / $total) * 100, 2);
            $this->command->info("📈 Taxa de conversão: {$successRate}%");
        }

        $this->command->info('═══════════════════════════════════');
    }
}
