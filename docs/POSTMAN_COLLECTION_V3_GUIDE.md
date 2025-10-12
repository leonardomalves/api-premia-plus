# 📮 Collection Postman v3 - API Premia Plus Estrutura Segmentada

## 🚀 **Nova Estrutura Segmentada**

A collection v3 foi criada para refletir a nova arquitetura com **estrutura segmentada** por tipo de usuário e responsabilidade.

## 📋 **Estrutura da Collection v3**

### **🔐 Autenticação (8 endpoints)**
- **Registrar Usuário** - Cria novo usuário
- **Login** - Autentica e retorna token
- **Logout** - Revoga token atual
- **Refresh Token** - Renova token
- **Meus Dados** - Dados do usuário autenticado
- **Perfil Completo** - Perfil detalhado
- **Atualizar Perfil** - Edita dados pessoais
- **Alterar Senha** - Muda senha

### **👤 Customer - Meus Dados (6 endpoints)**
- **Meu Perfil** - Dados do usuário autenticado (customer)
- **Minha Rede** - Rede de usuários patrocinados
- **Meu Patrocinador** - Dados do patrocinador
- **Minhas Estatísticas** - Estatísticas da rede
- **Atualizar Meu Perfil** - Edita perfil (customer)
- **Alterar Minha Senha** - Muda senha (customer)

### **👥 Customer - Usuários Específicos (3 endpoints)**
- **Rede de Usuário** - Rede de usuário específico (com permissão)
- **Patrocinador de Usuário** - Patrocinador de usuário específico (com permissão)
- **Estatísticas de Usuário** - Estatísticas de usuário específico (com permissão)

### **🔧 Administrator - Gerenciamento (5 endpoints)**
- **Listar Usuários** - Lista todos os usuários (admin apenas)
- **Ver Usuário** - Dados de usuário específico (admin apenas)
- **Criar Usuário** - Cria novo usuário (admin apenas)
- **Atualizar Usuário** - Atualiza usuário (admin apenas)
- **Excluir Usuário** - Exclui usuário (admin apenas)

### **🌐 Administrator - Rede e Estatísticas (5 endpoints)**
- **Rede de Usuário** - Rede de usuário específico (admin apenas)
- **Patrocinador de Usuário** - Patrocinador de usuário específico (admin apenas)
- **Estatísticas de Usuário** - Estatísticas de usuário específico (admin apenas)
- **Estatísticas do Sistema** - Estatísticas gerais do sistema (admin apenas)
- **Dashboard** - Dados do dashboard administrativo (admin apenas)

### **⚡ Administrator - Operações em Massa (3 endpoints)**
- **Atualização em Massa** - Atualiza múltiplos usuários (admin apenas)
- **Exclusão em Massa** - Exclui múltiplos usuários (admin apenas)
- **Exportar Usuários** - Exporta usuários para arquivo (admin apenas)

### **🔧 Utilitários (2 endpoints)**
- **Health Check** - Status da API
- **Test Endpoint** - Endpoint de teste

## 🎯 **Principais Diferenças da v3**

### **✅ Estrutura Segmentada**
- **Customer**: `/api/v1/customer/*`
- **Administrator**: `/api/v1/administrator/*`
- **Auth**: `/api/v1/*` (sem prefixo)
- **Shared**: `/api/v1/*` (compartilhadas)

### **✅ URLs Atualizadas**
- **Customer**: `/api/v1/customer/me`, `/api/v1/customer/network`, etc.
- **Administrator**: `/api/v1/administrator/users`, `/api/v1/administrator/statistics`, etc.
- **Auth**: `/api/v1/register`, `/api/v1/login`, etc.

### **✅ Novos Endpoints**
- **Dashboard**: `/api/v1/administrator/dashboard`
- **Criar Usuário**: `POST /api/v1/administrator/users`
- **Operações em Massa**: Bulk update, delete, export
- **Customer específico**: Perfil e senha separados

## 🧪 **Cenários de Teste Atualizados**

### **👤 Cenário 1: Fluxo Customer**
1. **Registrar usuário** → `POST /api/v1/register`
2. **Fazer login** → `POST /api/v1/login`
3. **Ver meu perfil** → `GET /api/v1/customer/me`
4. **Ver minha rede** → `GET /api/v1/customer/network`
5. **Ver meu patrocinador** → `GET /api/v1/customer/sponsor`
6. **Ver minhas estatísticas** → `GET /api/v1/customer/statistics`
7. **Atualizar meu perfil** → `PUT /api/v1/customer/profile`
8. **Alterar minha senha** → `POST /api/v1/customer/change-password`

### **🔧 Cenário 2: Fluxo Administrator**
1. **Login como admin** → `POST /api/v1/login`
2. **Ver dashboard** → `GET /api/v1/administrator/dashboard`
3. **Listar usuários** → `GET /api/v1/administrator/users`
4. **Criar usuário** → `POST /api/v1/administrator/users`
5. **Ver usuário específico** → `GET /api/v1/administrator/users/{uuid}`
6. **Atualizar usuário** → `PUT /api/v1/administrator/users/{uuid}`
7. **Ver estatísticas do sistema** → `GET /api/v1/administrator/statistics`

### **👥 Cenário 3: Acesso a Usuários Específicos**
1. **Login como usuário** → `POST /api/v1/login`
2. **Ver rede de outro usuário** → `GET /api/v1/customer/users/{uuid}/network`
3. **Ver patrocinador de outro usuário** → `GET /api/v1/customer/users/{uuid}/sponsor`
4. **Ver estatísticas de outro usuário** → `GET /api/v1/customer/users/{uuid}/statistics`

### **⚡ Cenário 4: Operações em Massa**
1. **Login como admin** → `POST /api/v1/login`
2. **Atualização em massa** → `POST /api/v1/administrator/users/bulk-update`
3. **Exclusão em massa** → `POST /api/v1/administrator/users/bulk-delete`
4. **Exportar usuários** → `POST /api/v1/administrator/users/export`

## 🔧 **Configuração da Collection v3**

### **1. Importar Collection**
```bash
# Importar o arquivo
docs/API_Premia_Plus_Postman_Collection_v3.json
```

### **2. Configurar Variáveis**
```json
{
    "base_url": "http://localhost:8000",
    "access_token": "",
    "user_uuid": "",
    "sponsor_uuid": "",
    "user_uuid_1": "",
    "user_uuid_2": "",
    "user_uuid_3": ""
}
```

### **3. Scripts de Automação**

#### **Login Automático (Tests)**
```javascript
if (pm.response.code === 200) {
    const response = pm.response.json();
    pm.environment.set("access_token", response.access_token);
    pm.environment.set("user_uuid", response.user.uuid);
}
```

#### **Token Automático (Pre-request)**
```javascript
const token = pm.environment.get("access_token");
if (token) {
    pm.request.headers.add({
        key: "Authorization",
        value: `Bearer ${token}`
    });
}
```

## 📊 **Exemplos de Respostas Atualizadas**

### **👤 Meu Perfil (Customer)**
```json
{
    "user": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "name": "João Silva",
        "email": "joao@example.com",
        "username": "joao123",
        "role": "user",
        "status": "active",
        "sponsor": {
            "id": 2,
            "uuid": "550e8400-e29b-41d4-a716-446655440001",
            "name": "Maria Silva"
        }
    }
}
```

### **🔧 Listar Usuários (Administrator)**
```json
{
    "users": {
        "data": [
            {
                "id": 1,
                "uuid": "550e8400-e29b-41d4-a716-446655440000",
                "name": "João Silva",
                "email": "joao@example.com",
                "role": "user",
                "status": "active"
            }
        ],
        "current_page": 1,
        "per_page": 15,
        "total": 1
    },
    "filters": {
        "search": "",
        "role": "",
        "status": "",
        "sponsor_uuid": ""
    }
}
```

### **⚡ Atualização em Massa**
```json
{
    "message": "3 usuários atualizados com sucesso",
    "updated_count": 3,
    "errors": []
}
```

### **🌐 Dashboard Administrativo**
```json
{
    "dashboard": {
        "total_users": 150,
        "active_users": 120,
        "new_users_today": 5,
        "revenue": {
            "total": 50000,
            "monthly": 5000
        },
        "top_performers": [
            {
                "uuid": "550e8400-e29b-41d4-a716-446655440000",
                "name": "João Silva",
                "network_size": 25
            }
        ]
    }
}
```

## 🚨 **Códigos de Status Atualizados**

- **200** - Sucesso
- **201** - Criado com sucesso
- **400** - Dados inválidos
- **401** - Não autenticado
- **403** - Acesso negado (sem permissão)
- **404** - Não encontrado
- **422** - Erro de validação
- **500** - Erro interno

## 💡 **Dicas de Uso da v3**

### **🎯 Segmentação por Responsabilidade**
- **Customer**: Use para funcionalidades do usuário comum
- **Administrator**: Use para funcionalidades administrativas
- **Auth**: Use para autenticação e perfil básico
- **Shared**: Use para utilitários

### **🔐 Segurança Melhorada**
- **URLs segmentadas**: Fácil identificação do tipo de usuário
- **Middleware específico**: Admin routes com middleware específico
- **Permissões claras**: Cada endpoint com responsabilidade definida

### **🧹 Organização**
- **Controllers separados**: Responsabilidades bem definidas
- **Rotas organizadas**: Por tipo de usuário e funcionalidade
- **Código limpo**: Manutenção mais fácil

## 🔗 **Links Úteis**

- **Collection v3**: `docs/API_Premia_Plus_Postman_Collection_v3.json`
- **Estrutura de Rotas**: `docs/ROUTE_STRUCTURE_PROPOSAL.md`
- **Implementação**: `docs/IMPLEMENTATION_SUMMARY.md`
- **Servidor**: `http://localhost:8000`

## 🎉 **Resultado Final**

A collection v3 oferece uma **experiência completa e organizada** para testar a API Premia Plus:

- ✅ **Estrutura segmentada** por tipo de usuário
- ✅ **URLs intuitivas** e organizadas
- ✅ **Endpoints completos** para todas as funcionalidades
- ✅ **Operações em massa** para administradores
- ✅ **Cenários de teste** bem definidos
- ✅ **Documentação detalhada** para cada endpoint

**🚀 Collection Postman v3 - API Premia Plus Estrutura Segmentada e Profissional!**
