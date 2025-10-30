# 📚 Collections Postman - Prêmia Club API

Este diretório contém as collections do Postman organizadas por domínio do sistema, seguindo as melhores práticas de organização e documentação da API.

## 🗂️ Estrutura por Domínio

```
docs/postman/collections/
├── Users/                      # Autenticação e gestão de usuários
├── Subscribers/               # Captação de leads e pré-cadastros
├── Raffles/                   # Rifas e sorteios
├── Commissions/              # Sistema de comissões
└── Orders/                   # Pedidos e carrinho de compras
```

## 📋 Collections Disponíveis

### 👥 Users & Authentication
**Arquivo:** `Users/Premia_Club_Users_API.postman_collection.json`

Funcionalidades:
- ✅ Login e Logout
- ✅ Registro de usuários
- ✅ Gestão de perfil
- ✅ Admin: CRUD de usuários
- ✅ Refresh tokens
- ✅ Recuperação de senha

### 📧 Subscribers (Lead Capture)
**Arquivo:** `Subscribers/Premia_Club_Lead_Capture_API.postman_collection.json`

Funcionalidades:
- ✅ Captação de leads públicos
- ✅ Verificação de status
- ✅ Descadastro (unsubscribe)
- ✅ Rate limiting configurado
- ✅ Tracking UTM integrado

### 🎯 Raffles
**Arquivo:** `Raffles/Premia_Club_Raffles_API.postman_collection.json`

Funcionalidades:
- 🚧 Listagem de rifas públicas
- 🚧 Detalhes da rifa
- 🚧 Compra de tickets
- 🚧 Admin: CRUD de rifas
- 🚧 Sorteios e resultados

### 💰 Commissions
**Arquivo:** `Commissions/Premia_Club_Commissions_API.postman_collection.json`

Funcionalidades:
- 🚧 Relatórios de comissões
- 🚧 Histórico de ganhos
- 🚧 Admin: gestão do sistema
- 🚧 Configurações de níveis

### 🛒 Orders & Cart
**Arquivo:** `Orders/Premia_Club_Orders_API.postman_collection.json`

Funcionalidades:
- ✅ Gestão de carrinho
- ✅ Criação de pedidos
- ✅ Histórico de compras
- ✅ Admin: gestão de pedidos
- ✅ Status de pagamento

**Legenda:** ✅ Implementado | 🚧 Em desenvolvimento | ❌ Pendente

## 🌍 Ambientes (Environments)

### Local Development
**Arquivo:** `Premia_Club_Local_Environment.postman_environment.json`

```json
{
  "base_url": "http://localhost:8000",
  "api_key": "local-api-key-12345",
  "auth_token": "",
  "user_uuid": "",
  "admin_email": "admin@premiaclub.local"
}
```

### Production
**Arquivo:** `Premia_Club_Production_Environment.postman_environment.json`

```json
{
  "base_url": "https://api.premiaclub.com.br",
  "api_key": "{{PRODUCTION_API_KEY}}",
  "auth_token": "",
  "user_uuid": "",
  "admin_email": "admin@premiaclub.com.br"
}
```

## 🚀 Como Usar

### 1. Importar Collections
1. Abra o Postman
2. Clique em "Import"
3. Selecione as collections desejadas dos diretórios por domínio
4. Importe os environments (Local/Production)

### 2. Configurar Environment
1. Selecione o environment adequado (Local/Production)
2. Configure as variáveis:
   - `base_url`: URL da API
   - `api_key`: Chave da API
   - `auth_token`: Token de autenticação (será preenchido automaticamente)

### 3. Executar Testes
1. **Ordem recomendada:**
   - Users → Login primeiro
   - Subscribers → Para leads públicos
   - Orders → Para compras (requer login)
   - Raffles → Para rifas específicas
   - Commissions → Para relatórios

2. **Scripts Automáticos:**
   - Tokens são salvos automaticamente
   - UUIDs são extraídos das respostas
   - Testes de validação incluídos

## 🧪 Testes Automatizados

Cada collection inclui:

### Pre-request Scripts
- Validação de variáveis
- Headers padrão
- Configurações de autenticação

### Test Scripts
- Validação de status HTTP
- Estrutura de resposta JSON
- Extração de dados importantes
- Armazenamento de UUIDs/tokens

### Exemplo de Test Script
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response structure", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('status', 'success');
    pm.expect(jsonData).to.have.property('data');
});

// Salvar token para próximas requisições
if (jsonData.data && jsonData.data.token) {
    pm.environment.set("auth_token", jsonData.data.token);
}
```

## 🔐 Autenticação

### API Key (Endpoints Públicos)
```http
X-API-Key: {{api_key}}
```

### Bearer Token (Endpoints Autenticados)
```http
Authorization: Bearer {{auth_token}}
```

### Rate Limiting
- **Lead Capture:** 5 requests/min
- **Status Check:** 10 requests/min  
- **Unsubscribe:** 3 requests/min
- **Authenticated:** 60 requests/min

## 📊 Monitoramento

### Métricas Incluídas
- `execution_time_ms`: Tempo de execução
- `memory_usage_mb`: Uso de memória
- `database_queries`: Queries executadas

### Health Check
```http
GET {{base_url}}/api/health
```

## 🛠️ Desenvolvimento

### Adicionar Nova Collection
1. Crie o diretório do domínio
2. Use a estrutura padrão das collections existentes
3. Inclua scripts de teste automatizados
4. Documente os endpoints no README

### Estrutura Padrão
```json
{
  "info": {
    "name": "Prêmia Club - [Domain]",
    "description": "Descrição do domínio...",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "📁 Categoria",
      "item": [ /* endpoints */ ]
    }
  ],
  "event": [ /* scripts globais */ ],
  "variable": [
    {
      "key": "domain",
      "value": "domain_name"
    }
  ]
}
```

## 📝 Changelog

### v2.0.0 - Reorganização por Domínio
- ✅ Separação por domínios de negócio
- ✅ Collections especializadas por contexto
- ✅ Documentação completa por domínio
- ✅ Scripts de teste padronizados

### v1.0.0 - Versão Inicial
- ✅ Collection única para lead capture
- ✅ Environments local e produção
- ✅ Testes básicos implementados

## 🤝 Contribuição

1. Mantenha a organização por domínios
2. Inclua testes automatizados
3. Documente novos endpoints
4. Use nomenclatura consistente
5. Valide em ambos os environments

## 📞 Suporte

Para dúvidas sobre as collections:
- 📧 Email: dev@premiaclub.com.br
- 💬 Slack: #api-development
- 📖 Wiki: [Link da documentação interna]