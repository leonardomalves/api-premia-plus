# 📮 Collection Postman v2 - API Premia Plus Segmentada

## 🚀 **Nova Estrutura Segmentada**

A collection foi atualizada para refletir a nova arquitetura com **Customer** e **Administrator** controllers separados.

## 📋 **Estrutura da Collection v2**

### **🔐 Autenticação (4 endpoints)**
- **Registrar Usuário** - Cria novo usuário
- **Login** - Autentica e retorna token
- **Logout** - Revoga token atual
- **Refresh Token** - Renova token

### **👤 Customer - Meus Dados (4 endpoints)**
- **Meu Perfil** - Dados do usuário autenticado
- **Minha Rede** - Rede de usuários patrocinados
- **Meu Patrocinador** - Dados do patrocinador
- **Minhas Estatísticas** - Estatísticas da rede

### **👥 Customer - Usuários Específicos (3 endpoints)**
- **Rede de Usuário** - Rede de usuário específico (com permissão)
- **Patrocinador de Usuário** - Patrocinador de usuário específico (com permissão)
- **Estatísticas de Usuário** - Estatísticas de usuário específico (com permissão)

### **🔧 Administrator - Gerenciamento (4 endpoints)**
- **Listar Usuários** - Lista todos os usuários (admin apenas)
- **Ver Usuário** - Dados de usuário específico (admin apenas)
- **Atualizar Usuário** - Atualiza usuário (admin apenas)
- **Excluir Usuário** - Exclui usuário (admin apenas)

### **🌐 Administrator - Rede e Estatísticas (4 endpoints)**
- **Rede de Usuário** - Rede de usuário específico (admin apenas)
- **Patrocinador de Usuário** - Patrocinador de usuário específico (admin apenas)
- **Estatísticas de Usuário** - Estatísticas de usuário específico (admin apenas)
- **Estatísticas do Sistema** - Estatísticas gerais do sistema (admin apenas)

### **⚡ Administrator - Operações em Massa (1 endpoint)**
- **Atualização em Massa** - Atualiza múltiplos usuários (admin apenas)

### **🔧 Utilitários (2 endpoints)**
- **Health Check** - Status da API
- **Test Endpoint** - Endpoint de teste

## 🎯 **Principais Diferenças da v2**

### **✅ Segmentação Clara**
- **Customer**: Funcionalidades do usuário comum
- **Administrator**: Funcionalidades administrativas
- **Rotas separadas**: Por tipo de usuário e responsabilidade

### **✅ Novas Variáveis**
- `{{user_uuid}}` - UUID do usuário
- `{{sponsor_uuid}}` - UUID do patrocinador
- `{{user_uuid_1}}`, `{{user_uuid_2}}`, `{{user_uuid_3}}` - Para operações em massa

### **✅ Endpoints Atualizados**
- **Customer**: `/api/v1/me`, `/api/v1/my-network`, etc.
- **Administrator**: `/api/v1/admin/users`, `/api/v1/admin/statistics`, etc.
- **UUIDs**: Todos os endpoints usam UUIDs em vez de IDs numéricos

## 🧪 **Cenários de Teste Atualizados**

### **👤 Cenário 1: Fluxo Customer**
1. **Registrar usuário** → `POST /api/v1/register`
2. **Fazer login** → `POST /api/v1/login`
3. **Ver meu perfil** → `GET /api/v1/me`
4. **Ver minha rede** → `GET /api/v1/my-network`
5. **Ver meu patrocinador** → `GET /api/v1/my-sponsor`
6. **Ver minhas estatísticas** → `GET /api/v1/my-statistics`

### **🔧 Cenário 2: Fluxo Administrator**
1. **Login como admin** → `POST /api/v1/login`
2. **Listar usuários** → `GET /api/v1/admin/users`
3. **Ver usuário específico** → `GET /api/v1/admin/users/{uuid}`
4. **Atualizar usuário** → `PUT /api/v1/admin/users/{uuid}`
5. **Ver estatísticas do sistema** → `GET /api/v1/admin/statistics`

### **👥 Cenário 3: Acesso a Usuários Específicos**
1. **Login como usuário** → `POST /api/v1/login`
2. **Ver rede de outro usuário** → `GET /api/v1/users/{uuid}/network`
3. **Ver patrocinador de outro usuário** → `GET /api/v1/users/{uuid}/sponsor`
4. **Ver estatísticas de outro usuário** → `GET /api/v1/users/{uuid}/statistics`

### **⚡ Cenário 4: Operações em Massa**
1. **Login como admin** → `POST /api/v1/login`
2. **Atualização em massa** → `POST /api/v1/admin/users/bulk-update`

## 🔧 **Configuração da Collection v2**

### **1. Importar Collection**
```bash
# Importar o arquivo
docs/API_Premia_Plus_Postman_Collection_v2.json
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

## 🚨 **Códigos de Status Atualizados**

- **200** - Sucesso
- **201** - Criado com sucesso
- **400** - Dados inválidos
- **401** - Não autenticado
- **403** - Acesso negado (sem permissão)
- **404** - Não encontrado
- **422** - Erro de validação
- **500** - Erro interno

## 💡 **Dicas de Uso da v2**

### **🎯 Segmentação por Responsabilidade**
- **Customer**: Use para funcionalidades do usuário comum
- **Administrator**: Use para funcionalidades administrativas
- **Permissões**: Cada usuário acessa apenas o que pode

### **🔐 Segurança Melhorada**
- **UUIDs**: URLs mais seguras e não previsíveis
- **Middleware**: Admin routes com middleware específico
- **Verificações**: Permissões verificadas em cada método

### **🧹 Organização**
- **Controllers separados**: Responsabilidades bem definidas
- **Rotas organizadas**: Por tipo de usuário
- **Código limpo**: Manutenção mais fácil

## 🔗 **Links Úteis**

- **Collection v2**: `docs/API_Premia_Plus_Postman_Collection_v2.json`
- **Documentação**: `docs/CONTROLLER_SEGMENTATION.md`
- **UUID Implementation**: `docs/UUID_IMPLEMENTATION.md`
- **Servidor**: `http://localhost:8000`

---

**🎉 Collection Postman v2 - API Premia Plus Segmentada e Profissional!**
