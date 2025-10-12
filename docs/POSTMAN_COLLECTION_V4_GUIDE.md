# 📋 API Premia Plus - Postman Collection v4

## 🎯 **Nova Versão com Planos**

Esta é a versão 4 da collection do Postman, agora incluindo **gerenciamento completo de planos** com rotas segmentadas para Customer e Administrator.

---

## 🚀 **Como Usar**

### 1. **Importar Collection**
1. Abra o Postman
2. Clique em **Import**
3. Selecione o arquivo `API_Premia_Plus_Postman_Collection_v4.json`
4. A collection será importada com todas as rotas organizadas

### 2. **Configurar Variáveis**
A collection já vem com variáveis pré-configuradas:

| Variável | Valor Padrão | Descrição |
|----------|--------------|-----------|
| `base_url` | `http://localhost:8000` | URL base da API |
| `auth_token` | (vazio) | Token de autenticação do usuário |
| `admin_token` | (vazio) | Token de autenticação do admin |
| `user_uuid` | (vazio) | UUID de um usuário específico |
| `plan_uuid` | (vazio) | UUID de um plano específico |

---

## 📁 **Estrutura da Collection**

### 🔐 **Autenticação**
- **Registrar Usuário** - Cria novo usuário
- **Login** - Autentica e retorna token
- **Logout** - Invalida token
- **Meu Perfil** - Dados do usuário autenticado

### 👤 **Customer - Usuário**
- **Meus Dados** - Dados do usuário autenticado
- **Atualizar Perfil** - Atualiza dados pessoais
- **Minha Rede** - Rede de patrocínio
- **Meu Patrocinador** - Dados do patrocinador
- **Minhas Estatísticas** - Estatísticas pessoais

### 📋 **Customer - Planos** ⭐ **NOVO**
- **Listar Planos** - Lista planos ativos com filtros
- **Ver Plano Específico** - Detalhes de um plano
- **Planos Promocionais** - Apenas planos promocionais
- **Buscar Planos** - Busca por nome/descrição e preço

### 👑 **Administrator - Usuários**
- **Listar Usuários** - Lista todos os usuários
- **Ver Usuário Específico** - Dados de um usuário
- **Criar Usuário** - Cria novo usuário
- **Atualizar Usuário** - Atualiza dados do usuário
- **Deletar Usuário** - Remove usuário

### 📋 **Administrator - Planos** ⭐ **NOVO**
- **Listar Planos (Admin)** - Lista com filtros avançados
- **Ver Plano Específico (Admin)** - Detalhes completos
- **Criar Plano** - Cria novo plano
- **Atualizar Plano** - Atualiza plano existente
- **Deletar Plano** - Remove plano
- **Ativar/Desativar Plano** - Alterna status
- **Estatísticas dos Planos** - Estatísticas gerais

### 🔧 **Sistema**
- **Health Check** - Status da API
- **Test Endpoint** - Endpoint de teste

---

## 🎯 **Fluxo de Teste Recomendado**

### **1. Configuração Inicial**
```bash
# 1. Fazer login como admin
POST /api/v1/login
{
    "email": "admin@premia.com",
    "password": "password123"
}

# 2. Copiar o token retornado para a variável admin_token
```

### **2. Testar Planos (Admin)**
```bash
# 1. Listar planos existentes
GET /api/v1/administrator/plans

# 2. Ver estatísticas
GET /api/v1/administrator/plans/statistics/overview

# 3. Criar novo plano
POST /api/v1/administrator/plans
{
    "name": "Plano Teste",
    "description": "Plano criado via API",
    "price": 299.90,
    "grant_tickets": 20,
    "status": "active",
    "commission_level_1": 7.50,
    "commission_level_2": 3.75,
    "commission_level_3": 1.50,
    "is_promotional": false,
    "overlap": 1,
    "start_date": "2025-10-12",
    "end_date": "2026-10-12"
}
```

### **3. Testar Planos (Customer)**
```bash
# 1. Fazer login como customer
POST /api/v1/login
{
    "email": "customer@premia.com",
    "password": "password123"
}

# 2. Listar planos disponíveis
GET /api/v1/customer/plans

# 3. Ver planos promocionais
GET /api/v1/customer/plans/promotional/list

# 4. Buscar planos
GET /api/v1/customer/plans/search?search=premium&price_range=medium
```

---

## 🔍 **Filtros e Parâmetros**

### **Customer - Listar Planos**
- `promotional` - true/false (filtrar promocionais)
- `min_price` - preço mínimo
- `max_price` - preço máximo
- `sort_by` - campo para ordenação (price, name, created_at)
- `sort_order` - asc/desc

### **Customer - Buscar Planos**
- `search` - termo de busca (nome/descrição)
- `price_range` - low/medium/high

### **Administrator - Listar Planos**
- `status` - active/inactive/suspended
- `promotional` - true/false
- `min_price` - preço mínimo
- `max_price` - preço máximo
- `search` - termo de busca
- `sort_by` - campo para ordenação
- `sort_order` - asc/desc
- `per_page` - itens por página

---

## 📊 **Exemplos de Resposta**

### **Listar Planos (Customer)**
```json
{
    "success": true,
    "message": "Planos listados com sucesso",
    "data": {
        "plans": [
            {
                "uuid": "123e4567-e89b-12d3-a456-426614174000",
                "name": "Plano Básico",
                "description": "Plano ideal para iniciantes",
                "price": 99.90,
                "grant_tickets": 10,
                "status": "active",
                "commission_level_1": 5.00,
                "commission_level_2": 2.50,
                "commission_level_3": 1.00,
                "is_promotional": false,
                "overlap": 0,
                "start_date": "2025-10-12T00:00:00.000000Z",
                "end_date": "2026-10-12T00:00:00.000000Z"
            }
        ],
        "total": 4,
        "filters": {
            "promotional": false,
            "sort_by": "price",
            "sort_order": "asc"
        }
    }
}
```

### **Estatísticas dos Planos (Admin)**
```json
{
    "success": true,
    "message": "Estatísticas dos planos",
    "data": {
        "statistics": {
            "total_plans": 4,
            "active_plans": 4,
            "inactive_plans": 0,
            "promotional_plans": 1,
            "average_price": 212.45,
            "min_price": 99.90,
            "max_price": 399.90,
            "total_tickets": 115
        }
    }
}
```

---

## 🚨 **Códigos de Status**

| Código | Descrição |
|--------|-----------|
| 200 | Sucesso |
| 201 | Criado com sucesso |
| 400 | Dados inválidos |
| 401 | Não autenticado |
| 403 | Acesso negado |
| 404 | Não encontrado |
| 422 | Erro de validação |
| 500 | Erro interno |

---

## 💡 **Dicas Importantes**

1. **Tokens**: Sempre use os tokens corretos para cada tipo de usuário
2. **UUIDs**: Use UUIDs válidos nas rotas que os requerem
3. **Filtros**: Aproveite os filtros para encontrar dados específicos
4. **Paginação**: Use `per_page` para controlar a quantidade de resultados
5. **Ordenação**: Use `sort_by` e `sort_order` para organizar os resultados

---

## 🎉 **Pronto para Usar!**

A collection v4 está completa com todas as funcionalidades de planos implementadas. Agora você pode testar tanto as operações de leitura (Customer) quanto as operações completas (Administrator) para gerenciar planos na API Premia Plus!
