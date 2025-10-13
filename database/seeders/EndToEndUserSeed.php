<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Core\HttpClient;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EndToEndUserSeed extends Seeder
{
    private $baseUrl = 'http://localhost:8000/api/v1';
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('role', 'user')->get();
        $httpClient = new HttpClient();

        foreach ($users as $user) {
            try {
                $authEndpoint = "{$this->baseUrl}/login";
                $authData = [
                    'email' => $user->email,
                    'password' => 'password',
                ];
                
                $authResponse = $httpClient->apiRequest($authEndpoint, $authData, [], 'POST');
                
                // Converter para array se for objeto
                if (is_object($authResponse)) {
                    $authResponse = json_decode(json_encode($authResponse), true);
                }
                
                // Verificar se a resposta tem status 200 e contém o token
                if ($authResponse && isset($authResponse['status']) && $authResponse['status'] == 200 && isset($authResponse['content']['access_token'])) {
                    echo "✅ Login realizado com sucesso para: {$user->email}\n";
                    
                    $token = $authResponse['content']['access_token'];
                    
                    // Testar endpoint protegido
                    $protectedEndpoint = "{$this->baseUrl}/profile";
                    $headers = ['Authorization' => 'Bearer ' . $token];
                    $profileResponse = $httpClient->apiRequest($protectedEndpoint, [], $headers, 'GET');
                    
                    // Converter para array se for objeto
                    if (is_object($profileResponse)) {
                        $profileResponse = json_decode(json_encode($profileResponse), true);
                    }
                    
                    if ($profileResponse && isset($profileResponse['status']) && $profileResponse['status'] == 200) {
                        echo "✅ Perfil acessado com sucesso para: {$user->email}\n";
                        $profileData = $profileResponse['content'];
                        if (isset($profileData['user'])) {
                            echo "   - Nome: {$profileData['user']['name']}\n";
                            echo "   - Email: {$profileData['user']['email']}\n";
                            echo "   - Role: {$profileData['user']['role']}\n";
                        }
                        
                        // Listar planos (produtos)
                        $this->testListPlans($httpClient, $token, $user->email);
                        
                        // Visualizar um plano específico
                        $this->testViewPlan($httpClient, $token, $user->email);
                        
                    } else {
                        echo "❌ Falha ao acessar perfil para: {$user->email}\n";
                    }
                } else {
                    echo "❌ Falha no login para: {$user->email}\n";
                    echo "Status: " . ($authResponse['status'] ?? 'N/A') . "\n";
                    echo "Resposta: " . json_encode($authResponse) . "\n";
                }
            } catch (\Exception $e) {
                echo "❌ Erro no teste para {$user->email}: " . $e->getMessage() . "\n";
            }
        }
    }
    
    /**
     * Testa a listagem de planos para um usuário
     */
    private function testListPlans($httpClient, $token, $userEmail): void
    {
        try {
            $plansEndpoint = "{$this->baseUrl}/customer/plans";
            $headers = ['Authorization' => 'Bearer ' . $token];
            $plansResponse = $httpClient->apiRequest($plansEndpoint, [], $headers, 'GET');
            
            // Converter para array se for objeto
            if (is_object($plansResponse)) {
                $plansResponse = json_decode(json_encode($plansResponse), true);
            }
            
            // Extrair dados da resposta
            $responseData = $this->extractResponseData($plansResponse);
            
            if ($this->isValidResponse($responseData)) {
                echo "✅ Planos listados com sucesso para: {$userEmail}\n";
                $this->displayPlansList($responseData['data']);
            } else {
                echo "❌ Falha ao listar planos para: {$userEmail}\n";
                $this->debugResponse($plansResponse);
            }
        } catch (\Exception $e) {
            echo "❌ Erro ao listar planos para {$userEmail}: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Testa a visualização de um plano específico para um usuário
     */
    private function testViewPlan($httpClient, $token, $userEmail): void
    {
        try {
            // Primeiro, buscar um plano para obter seu UUID
            $plansEndpoint = "{$this->baseUrl}/customer/plans";
            $headers = ['Authorization' => 'Bearer ' . $token];
            $plansResponse = $httpClient->apiRequest($plansEndpoint, [], $headers, 'GET');
            
            // Converter para array se for objeto
            if (is_object($plansResponse)) {
                $plansResponse = json_decode(json_encode($plansResponse), true);
            }
            
            // Extrair dados da resposta
            $responseData = $this->extractResponseData($plansResponse);
            
            if ($this->isValidResponse($responseData) && isset($responseData['data']['plans']) && count($responseData['data']['plans']) > 0) {
                // Pegar o primeiro plano para visualizar
                $firstPlan = $responseData['data']['plans'][0];
                $planUuid = $firstPlan['uuid'];
                
                // Visualizar o plano específico
                $planEndpoint = "{$this->baseUrl}/customer/plans/{$planUuid}";
                $planResponse = $httpClient->apiRequest($planEndpoint, [], $headers, 'GET');
                
                // Converter para array se for objeto
                if (is_object($planResponse)) {
                    $planResponse = json_decode(json_encode($planResponse), true);
                }
                
                // Extrair dados da resposta do plano
                $planResponseData = $this->extractResponseData($planResponse);
                
                if ($this->isValidResponse($planResponseData)) {
                    echo "✅ Plano visualizado com sucesso para: {$userEmail}\n";
                    $this->displayPlanDetails($planResponseData['data']['plan']);
                } else {
                    echo "❌ Falha ao visualizar plano para: {$userEmail}\n";
                    $this->debugResponse($planResponse);
                }
            } else {
                echo "⚠️ Nenhum plano disponível para visualizar para: {$userEmail}\n";
            }
        } catch (\Exception $e) {
            echo "❌ Erro ao visualizar plano para {$userEmail}: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Extrai dados da resposta da API
     */
    private function extractResponseData($response): ?array
    {
        if (isset($response['content'])) {
            return $response['content'];
        }
        return $response;
    }
    
    /**
     * Verifica se a resposta é válida
     */
    private function isValidResponse($responseData): bool
    {
        return $responseData && isset($responseData['success']) && $responseData['success'] === true;
    }
    
    /**
     * Exibe a lista de planos
     */
    private function displayPlansList($plansData): void
    {
        if (isset($plansData['plans']) && is_array($plansData['plans'])) {
            echo "   📋 Total de planos: {$plansData['total']}\n";
            foreach ($plansData['plans'] as $plan) {
                echo "   - {$plan['name']}: R$ " . number_format($plan['price'], 2, ',', '.') . "\n";
            }
        }
    }
    
    /**
     * Exibe os detalhes de um plano
     */
    private function displayPlanDetails($plan): void
    {
        if (isset($plan)) {
            echo "   📄 Detalhes do plano:\n";
            echo "   - Nome: {$plan['name']}\n";
            echo "   - Descrição: {$plan['description']}\n";
            echo "   - Preço: R$ " . number_format($plan['price'], 2, ',', '.') . "\n";
            echo "   - Tickets: {$plan['grant_tickets']}\n";
            echo "   - Status: {$plan['status']}\n";
            echo "   - Promocional: " . ($plan['is_promotional'] ? 'Sim' : 'Não') . "\n";
        }
    }
    
    /**
     * Exibe informações de debug da resposta
     */
    private function debugResponse($response): void
    {
        echo "   🔍 Debug - Resposta: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
    }
}
