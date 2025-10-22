# API Prêmia Plus - Postman Collection v8.0

## 📋 Visão Geral

Collection completa da API Prêmia Plus versão 8.0 com o novo sistema de Wallet e Aplicação em Rifas.

**Arquivo:** `API_Premia_Plus_Postman_Collection_v8.json`  
**Versão:** 8.0.0  
**Data:** 22/10/2025  
**Total de Endpoints:** 65+

## 🆕 Novidades v8.0

### Sistema de Wallet
- ✅ Carteira digital com saldo em reais
- ✅ Financial Statements (crédito/débito)
- ✅ Rastreabilidade completa de transações

### Sistema de Orders
- ✅ Listar minhas compras (customer)
- ✅ Detalhes de compra específica (customer)
- ✅ Gerenciar todas as orders (admin)
- ✅ Filtros avançados e estatísticas

### Aplicação em Rifas
- ✅ Pagamento via saldo da wallet
- ✅ Uma aplicação por usuário por rifa
- ✅ Tickets sorteados aleatoriamente do pool
- ✅ Quantidade mínima configurável
- ✅ Processamento assíncrono via Job Queue

### Endpoints Atualizados
- `POST /customer/raffles/{uuid}/apply` - Novo endpoint de aplicação
- `GET /customer/raffles/my-applications` - Listar aplicações
- `GET /customer/raffles/{uuid}/my-tickets` - Tickets em rifa específica
- `POST /administrator/raffles` - Criar rifa (schema atualizado)

### Removidos
- ❌ `POST /customer/raffles/{uuid}/tickets` (endpoint antigo)
- ❌ `DELETE /customer/raffles/{uuid}/tickets` (cancelamento de tickets)
- ❌ Campos: `total_tickets`, `max_tickets_per_user`, `min_ticket_level`

## 📦 Instalação

### 1. Importar Collection

1. Abra o Postman
2. Clique em **Import** (canto superior esquerdo)
3. Selecione o arquivo `API_Premia_Plus_Postman_Collection_v8.json`
4. Clique em **Import**

### 2. Configurar Variáveis

Após importar, configure as variáveis:

| Variável | Valor Padrão | Descrição |
|----------|--------------|-----------|
| `base_url` | `http://localhost:8000/api/v1` | URL base da API |
| `token` | (vazio) | Token de autenticação (preenchido automaticamente) |
| `user_uuid` | (vazio) | UUID do usuário para testes |
| `plan_uuid` | (vazio) | UUID do plano para testes |
| `raffle_uuid` | (vazio) | UUID da rifa para testes |

**Como alterar:**
- Clique na collection → Aba **Variables**
- Atualize os valores conforme seu ambiente

### 3. Configurar Ambiente (Opcional)

Para diferentes ambientes (dev, staging, prod):

1. Crie um Environment no Postman
2. Adicione as variáveis:
   ```
   base_url: http://localhost:8000/api/v1
   token: (será preenchido automaticamente)
   ```
3. Selecione o ambiente no dropdown superior direito

## 🚀 Uso Rápido

### Autenticação Automática

A collection possui scripts que salvam automaticamente o token após login/registro:

1. **Fazer Login:**
   - Vá em `Auth → Login`
   - Preencha email e senha
   - Execute (Send)
   - ✅ Token salvo automaticamente

2. **Usar Endpoints Protegidos:**
   - Todos os endpoints já têm autenticação configurada
   - O token é usado automaticamente via `{{token}}`

### Fluxo Básico de Teste

```
1. Register/Login → Token salvo automaticamente
2. Customer → Plans → List Plans
3. Customer → Cart → Add to Cart (usar plan_uuid da resposta)
4. Customer → Cart → Checkout
5. Customer → Raffles → List Raffles
6. Customer → Raffles → Apply to Raffle (usar raffle_uuid)
7. Customer → Raffles → My Tickets in Raffle
```

## 📚 Estrutura da Collection

### 1. Auth (8 endpoints)
- Register
- Login
- Logout
- Me
- Refresh Token
- Profile
- Update Profile
- Change Password

### 2. Customer (25+ endpoints)

#### Plans
- List Plans
- Get Plan
- Promotional Plans
- Search Plans

#### Cart
- Add to Cart
- View Cart
- Remove from Cart
- Clear Cart
- Checkout

#### Wallet ⭐ **NOVO**
- Get Wallet (saldo + últimas transações)
- Get Balance (apenas saldo)
- Get Statements (extratos com filtros)
- Get Transactions (histórico + analytics)

#### Orders ⭐ **NOVO**
- List My Orders (minhas compras)
- Get Order Details (detalhes de uma compra)

#### Raffles ⭐ **ATUALIZADO v8.0**
- List Raffles
- Get Raffle
- **Apply to Raffle** (novo endpoint)
- **My Applications** (novo endpoint)
- My Tickets in Raffle

#### Network
- My Network
- My Sponsor
- My Statistics

### 3. Administrator (35+ endpoints)

#### Users
- List Users
- Get User
- Create User
- Update User
- Delete User
- User Network
- User Sponsor
- User Statistics

#### Plans
- List Plans
- Get Plan
- Create Plan
- Update Plan
- Delete Plan
- Toggle Plan Status

#### Orders ⭐ **NOVO**
- List All Orders (gerenciar todas as compras)

#### Raffles ⭐ **ATUALIZADO v8.0**
- List Raffles
- Get Raffle
- **Create Raffle** (schema atualizado)
- **Update Raffle** (schema atualizado)
- Delete Raffle
- Toggle Raffle Status

#### System
- Dashboard
- Statistics

### 4. Health & System (3 endpoints)
- Health Check
- Health Check Detailed
- Test

## 🎯 Exemplos de Uso

### Exemplo 1: Aplicar em Rifa (Sistema v8.0)

```json
POST /customer/raffles/{raffle_uuid}/apply

Body:
{
    "quantity": 200
}

Response (201):
{
    "success": true,
    "message": "Aplicação realizada com sucesso",
    "user_id": 1,
    "raffle_id": 1,
    "raffle_title": "iPhone 15 Pro Max",
    "tickets_count": 200,
    "total_cost": 2.00,
    "ticket_numbers": ["0000001", "0000002", "..."],
    "remaining_balance": 147.50,
    "duration_ms": 5523.25
}
```

**Validações Automáticas:**
- ✅ Saldo suficiente na wallet
- ✅ Rifa com status 'active'
- ✅ Usuário não aplicou anteriormente
- ✅ Tickets disponíveis no pool
- ✅ Quantidade >= min_tickets_required

### Exemplo 2: Criar Rifa (Admin)

```json
POST /administrator/raffles

Body:
{
    "title": "iPhone 15 Pro Max",
    "description": "iPhone 15 Pro Max 256GB Azul Titânio",
    "prize_value": 8999.00,
    "operation_cost": 899.00,
    "unit_ticket_value": 0.01,
    "liquidity_ratio": 85.0,
    "min_tickets_required": 200,
    "draw_date": "2025-02-15 20:00:00",
    "status": "pending",
    "notes": "Sorteio ao vivo no Instagram"
}
```

**Campos Removidos na v8.0:**
- ❌ `total_tickets` (calculado automaticamente)
- ❌ `max_tickets_per_user` (sem limite)
- ❌ `min_ticket_level` (sistema não usa níveis)
- ❌ `prize_description` (usar `description`)

### Exemplo 3: Verificar Minhas Aplicações

```json
GET /customer/raffles/my-applications?page=1&per_page=15

Response (200):
{
    "applications": {
        "data": [
            {
                "raffle": {
                    "id": 1,
                    "uuid": "...",
                    "title": "iPhone 15 Pro Max",
                    "status": "active"
                },
                "tickets_count": 200,
                "total_paid": 2.00,
                "ticket_numbers": ["0000001", "0000002", "..."],
                "applied_at": "2025-01-20T10:00:00.000000Z"
            }
        ]
    }
}
```

## 🔧 Variáveis Dinâmicas

A collection usa variáveis do Postman para facilitar testes:

```javascript
// Variáveis disponíveis:
{{base_url}}      // URL base da API
{{token}}         // Token de autenticação (auto-preenchido)
{{user_uuid}}     // UUID do usuário
{{plan_uuid}}     // UUID do plano
{{raffle_uuid}}   // UUID da rifa
```

**Como usar:**
1. Execute um endpoint que retorna UUID
2. Copie o UUID da resposta
3. Cole na variável correspondente (Variables tab)
4. Use `{{variable}}` nos próximos requests

## 🧪 Scripts de Teste

### Auto-save Token (Login/Register)

```javascript
if (pm.response.code === 200 || pm.response.code === 201) {
    const response = pm.response.json();
    pm.collectionVariables.set('token', response.access_token);
    pm.environment.set('token', response.access_token);
}
```

### Validação de Aplicação em Rifa

```javascript
if (pm.response.code === 201) {
    const response = pm.response.json();
    console.log('Aplicação realizada!');
    console.log('Tickets: ' + response.tickets_count);
    console.log('Custo: R$ ' + response.total_cost);
    console.log('Saldo restante: R$ ' + response.remaining_balance);
}
```

## 🎨 Organização

```
API Prêmia Plus v8
├── Auth (Autenticação)
├── Customer
│   ├── Plans (Planos)
│   ├── Cart (Carrinho)
│   ├── Wallet (Carteira) ⭐ NOVO
│   ├── Orders (Compras) ⭐ NOVO
│   ├── Raffles (Rifas) ⭐ ATUALIZADO
│   └── Network (Rede)
├── Administrator
│   ├── Users (Usuários)
│   ├── Plans (Planos)
│   ├── Orders (Gestão de Compras) ⭐ NOVO
│   ├── Raffles (Rifas) ⭐ ATUALIZADO
│   └── System (Dashboard, Stats)
└── Health & System
```

## 📊 Códigos de Status HTTP

| Código | Significado | Quando Ocorre |
|--------|-------------|---------------|
| 200 | OK | Operação bem-sucedida |
| 201 | Created | Recurso criado (aplicação, registro) |
| 400 | Bad Request | Validação de negócio (saldo insuficiente, já aplicou) |
| 401 | Unauthorized | Token inválido/expirado |
| 403 | Forbidden | Sem permissão (não é admin) |
| 404 | Not Found | Recurso não encontrado |
| 422 | Unprocessable Entity | Validação de campos (required, min, max) |
| 500 | Internal Server Error | Erro no servidor |

## 🆚 Diferenças v7.0 → v8.0

### Endpoints Alterados

| v7.0 | v8.0 | Mudança |
|------|------|---------|
| `POST /raffles/{uuid}/tickets` | `POST /raffles/{uuid}/apply` | Novo endpoint com wallet |
| `DELETE /raffles/{uuid}/tickets` | ❌ Removido | Sem cancelamento |
| - | `GET /raffles/my-applications` | Novo endpoint |

### Schema de Rifa

| v7.0 | v8.0 | Status |
|------|------|--------|
| `total_tickets` | ❌ Removido | Calculado automaticamente |
| `tickets_required` | `min_tickets_required` | Renomeado |
| `max_tickets_per_user` | ❌ Removido | Sem limite |
| `min_ticket_level` | ❌ Removido | Sistema não usa níveis |
| `prize_description` | `description` | Usar campo description |
| - | `notes` | Novo campo (observações) |

### Resposta de Aplicação

**v7.0:**
```json
{
    "message": "Tickets aplicados com sucesso",
    "applied_tickets": [...],
    "remaining_tickets": 45
}
```

**v8.0:**
```json
{
    "success": true,
    "message": "Aplicação realizada com sucesso",
    "tickets_count": 200,
    "total_cost": 2.00,
    "ticket_numbers": ["0000001", "..."],
    "remaining_balance": 147.50,
    "duration_ms": 5523.25
}
```

## 🐛 Troubleshooting

### Token Não Salvo Automaticamente

**Problema:** Após login, endpoints retornam 401

**Solução:**
1. Verifique se o script de teste está ativo (Pre-request/Tests tab)
2. Execute Login novamente
3. Ou copie manualmente o token da resposta para `{{token}}`

### Variáveis Não Funcionam

**Problema:** `{{raffle_uuid}}` aparece literalmente na request

**Solução:**
1. Vá em Variables tab da collection
2. Preencha o valor da variável
3. Salve (Ctrl+S)

### Erro 422 em Create Raffle

**Problema:** Campos obrigatórios faltando

**Solução:**
- Certifique-se de enviar TODOS os campos obrigatórios:
  - title, prize_value, operation_cost, unit_ticket_value
  - liquidity_ratio, min_tickets_required, draw_date, status
- NÃO envie campos removidos (total_tickets, max_tickets_per_user, etc.)

### Erro 400: "Saldo insuficiente"

**Problema:** Wallet sem saldo para aplicar em rifa

**Solução:**
1. Faça checkout de um plano primeiro (Cart → Checkout)
2. Verifique o saldo via endpoint de estatísticas
3. Aplique em rifa com quantidade adequada ao saldo

## 📝 Notas de Desenvolvimento

### Sistema de Wallet
- Saldo em reais (decimal)
- Financial Statements registram crédito/débito
- Débito automático na aplicação em rifa

### Processamento Assíncrono
- Job: `UserApplyToRaffleJob`
- Fila: `raffle-applications`
- Retry: 3 tentativas, backoff 5s

### Validações de Negócio
- Rifa deve estar 'active'
- Usuário pode aplicar apenas 1x por rifa
- Saldo >= (quantity * unit_ticket_value)
- Tickets disponíveis no pool

## 📞 Suporte

- **Documentação Completa:** `docs/API_DOCUMENTATION.md`
- **Changelog:** Ver seção Changelog na documentação
- **Issues:** GitHub repository

## 🔄 Histórico de Versões

### v8.0.0 (2025-10-22)
- ✅ Sistema de Wallet implementado
- ✅ Sistema de Orders (compras) implementado
- ✅ Novo endpoint de aplicação em rifas
- ✅ Financial Statements
- ✅ Pool de tickets numerados
- ✅ Processamento assíncrono
- ❌ Removidos campos obsoletos
- 📝 256 testes passando (14 testes de orders + 8 testes de wallet)

### v7.0.0 (2025-10-20)
- Sistema de WalletTicket
- Aplicação com tickets do wallet
- Cancelamento de tickets pendentes

### v1.0.0 (2025-01-01)
- Versão inicial
- CRUD básico de usuários, planos, rifas
- Sistema de autenticação

---

**Desenvolvido por:** Neutrino Soluções em Tecnologia  
**Última Atualização:** 22/10/2025  
**Versão da Collection:** 8.0.0
