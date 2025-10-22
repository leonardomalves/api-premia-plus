# API Prêmia Plus - Documentação Completa

## Visão Geral
API RESTful para sistema de sorteios e gestão de tickets, implementada em Laravel 11 com Sanctum para autenticação.

**Base URL:** `/api/v1`  
**Autenticação:** Bearer Token (Laravel Sanctum)  
**Formato de resposta:** JSON  

---

## Estrutura de Resposta Padrão

### Resposta de Sucesso
```json
{
  "success": true,
  "message": "Operação realizada com sucesso",
  "data": {
    // dados da resposta
  }
}
```

### Resposta de Erro
```json
{
  "success": false,
  "message": "Descrição do erro",
  "error": "Detalhes técnicos",
  "errors": {
    // erros de validação (quando aplicável)
  }
}
```

---

## Sistema de Referência/Sponsor

### Como Funciona
O sistema permite que usuários compartilhem links com seu username como patrocinador, garantindo que novos registros sejam automaticamente vinculados ao referenciador.

### Implementação Frontend

#### 1. Captura Automática do Sponsor
```javascript
// Executar em todas as páginas da aplicação
function captureSponsor() {
  const urlParams = new URLSearchParams(window.location.search);
  const sponsor = urlParams.get('sponsor');
  
  if (sponsor && sponsor.trim() !== '') {
    // Armazenar sponsor no localStorage
    localStorage.setItem('referral_sponsor', sponsor);
    
    // Opcional: Remover o parâmetro da URL para UX limpa
    const newUrl = window.location.pathname;
    window.history.replaceState({}, document.title, newUrl);
    
    console.log(`Sponsor capturado: ${sponsor}`);
  }
}

// Executar na inicialização de cada página
captureSponsor();
```

#### 2. Envio no Registro
```javascript
// No formulário de registro
function handleRegister(formData) {
  const registerPayload = {
    name: formData.name,
    email: formData.email,
    username: formData.username,
    password: formData.password,
    password_confirmation: formData.passwordConfirmation,
    phone: formData.phone || null
  };
  
  // Sempre incluir sponsor se existir
  const savedSponsor = localStorage.getItem('referral_sponsor');
  if (savedSponsor) {
    registerPayload.sponsor = savedSponsor;
  }
  
  // Fazer chamada da API
  return fetch('/api/v1/register', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify(registerPayload)
  });
}
```

#### 3. Links de Referência
```javascript
// Função para gerar links de compartilhamento
function generateReferralLink(username, targetPage = '') {
  const baseUrl = window.location.origin;
  const page = targetPage || '/register';
  return `${baseUrl}${page}?sponsor=${username}`;
}

// Exemplos de uso:
// generateReferralLink('joaosilva') → "https://app.com/register?sponsor=joaosilva"
// generateReferralLink('joaosilva', '/plans') → "https://app.com/plans?sponsor=joaosilva"
```

#### 4. Exibição Visual (Opcional)
```javascript
// Mostrar banner indicando sponsor ativo
function showSponsorBanner() {
  const sponsor = localStorage.getItem('referral_sponsor');
  if (sponsor) {
    // Exibir banner ou toast informando que o usuário chegou via referência
    showNotification(`Você foi referenciado por: ${sponsor}`);
  }
}
```

### Casos de Uso
- **Marketing de Afiliação**: Usuários compartilham links personalizados
- **Programas de Referência**: Comissões automáticas para patrocinadores
- **Campanhas**: Links específicos para diferentes canais de marketing
- **Redes Sociais**: Compartilhamento viral com rastreamento automático

### URLs de Exemplo
```
https://app.premiaplus.com/register?sponsor=joaosilva
https://app.premiaplus.com/plans?sponsor=mariavendedora
https://app.premiaplus.com/about?sponsor=carlosafiliado
https://app.premiaplus.com/?sponsor=anainfluencer
```

---

## Autenticação

### 1. Registro de Usuário
**POST** `/register`

**Acesso:** Público

**Payload:**
```json
{
  "name": "João Silva",
  "email": "joao@email.com",
  "username": "joaosilva",
  "password": "minimo8caracteres",
  "password_confirmation": "minimo8caracteres",
  "phone": "11999999999",
  "sponsor": "username_do_patrocinador"
}
```

**Campos Obrigatórios:**
- `name` (string, max:255)
- `email` (string, email, único)
- `username` (string, max:255, único)
- `password` (string, min:8, confirmed)

**Campos Opcionais:**
- `phone` (string, max:20)
- `sponsor` (string, username existente)

> **⚠️ Importante - Sistema de Referência/Sponsor:**
> 
> O campo `sponsor` deve ser capturado automaticamente pela aplicação frontend através de query string `?sponsor=username` em qualquer URL do sistema. 
> 
> **Fluxo de implementação:**
> 1. **Captura**: Ao detectar `?sponsor=username` em qualquer URL, armazenar no `localStorage`
> 2. **Persistência**: Manter o sponsor no `localStorage` durante toda a sessão do usuário
> 3. **Envio**: Sempre incluir o sponsor armazenado no payload do registro, mesmo que o usuário navegue para outras páginas
> 4. **Exemplo de URLs**: 
>    - `https://app.com/register?sponsor=joaosilva`
>    - `https://app.com/plans?sponsor=mariasousa` 
>    - `https://app.com/about?sponsor=carlosvendedor`
> 
> **Implementação JavaScript:**
> ```javascript
> // Capturar sponsor da URL
> const urlParams = new URLSearchParams(window.location.search);
> const sponsor = urlParams.get('sponsor');
> if (sponsor) {
>   localStorage.setItem('referral_sponsor', sponsor);
> }
> 
> // Recuperar sponsor para o registro
> const savedSponsor = localStorage.getItem('referral_sponsor');
> if (savedSponsor) {
>   registerPayload.sponsor = savedSponsor;
> }
> ```

**Resposta de Sucesso (201):**
```json
{
  "message": "Usuário registrado com sucesso",
  "user": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "name": "João Silva",
    "email": "joao@email.com",
    "username": "joaosilva",
    "role": "user",
    "status": "active",
    "sponsor": {
      "id": 2,
      "name": "Patrocinador",
      "username": "patrocinador"
    }
  },
  "access_token": "1|abc123...",
  "token_type": "Bearer"
}
```

### 2. Login
**POST** `/login`

**Acesso:** Público

**Payload:**
```json
{
  "email": "joao@email.com",
  "password": "senha123"
}
```

**Campos Obrigatórios:**
- `email` (string, email)
- `password` (string)

**Resposta de Sucesso (200):**
```json
{
  "message": "Login realizado com sucesso",
  "user": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "name": "João Silva",
    "email": "joao@email.com",
    "username": "joaosilva",
    "role": "user",
    "status": "active"
  },
  "access_token": "1|abc123...",
  "token_type": "Bearer"
}
```

### 3. Logout
**POST** `/logout`

**Acesso:** Autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "message": "Successfully logged out"
}
```

### 4. Renovar Token
**POST** `/refresh`

**Acesso:** Autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "message": "Token renovado com sucesso",
  "access_token": "2|xyz789...",
  "token_type": "Bearer"
}
```

### 5. Dados do Usuário Logado
**GET** `/me`

**Acesso:** Autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "user": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "name": "João Silva",
    "email": "joao@email.com",
    "username": "joaosilva",
    "role": "user",
    "status": "active",
    "created_at": "2025-01-01T00:00:00.000000Z",
    "updated_at": "2025-01-01T00:00:00.000000Z"
  }
}
```

### 6. Perfil do Usuário
**GET** `/profile`

**Acesso:** Autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "user": {
    "id": 1,
    "name": "João Silva",
    "email": "joao@email.com",
    "phone": "11999999999",
    "role": "user",
    "status": "active",
    "sponsor_id": 2,
    "created_at": "2025-01-01T00:00:00.000000Z",
    "updated_at": "2025-01-01T00:00:00.000000Z"
  }
}
```

### 7. Atualizar Perfil
**PUT** `/profile`

**Acesso:** Autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Payload:**
```json
{
  "name": "João Silva Santos",
  "phone": "11888888888"
}
```

**Campos Opcionais:**
- `name` (string, max:255)
- `phone` (string, max:20)

**Resposta de Sucesso (200):**
```json
{
  "message": "Perfil atualizado com sucesso",
  "user": {
    "id": 1,
    "name": "João Silva Santos",
    "email": "joao@email.com",
    "phone": "11888888888",
    "role": "user",
    "status": "active"
  }
}
```

### 8. Alterar Senha
**POST** `/change-password`

**Acesso:** Autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Payload:**
```json
{
  "current_password": "senhaAtual123",
  "password": "novaSenha123",
  "password_confirmation": "novaSenha123"
}
```

**Campos Obrigatórios:**
- `current_password` (string)
- `password` (string, min:8, confirmed)

**Resposta de Sucesso (200):**
```json
{
  "message": "Password changed successfully"
}
```

---

## Endpoints para Clientes (Customer)

> **Prefixo:** `/customer`  
> **Middleware:** `auth:sanctum`

### 1. Dados do Cliente Logado
**GET** `/customer/me`

**Acesso:** Cliente autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "user": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "name": "João Silva",
    "email": "joao@email.com",
    "username": "joaosilva",
    "phone": "11999999999",
    "role": "user",
    "status": "active",
    "sponsor_id": 2,
    "created_at": "2025-01-01T00:00:00.000000Z",
    "updated_at": "2025-01-01T00:00:00.000000Z"
  }
}
```

### 2. Atualizar Perfil do Cliente
**PUT** `/customer/profile`

**Acesso:** Cliente autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Payload:**
```json
{
  "name": "João Silva Santos",
  "phone": "11888888888",
  "email": "novo@email.com",
  "username": "novoUsername"
}
```

**Campos Opcionais:**
- `name` (string, max:255)
- `phone` (string, max:20)
- `email` (string, email, único exceto próprio)
- `username` (string, max:255, único exceto próprio)

### 3. Rede do Cliente (Usuários Patrocinados)
**GET** `/customer/network`

**Acesso:** Cliente autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "network": [
    {
      "id": 3,
      "uuid": "550e8400-e29b-41d4-a716-446655440001",
      "name": "Cliente Patrocinado 1",
      "username": "cliente1",
      "email": "cliente1@email.com",
      "status": "active",
      "created_at": "2025-01-02T00:00:00.000000Z"
    }
  ],
  "total_sponsored": 1,
  "active_sponsored": 1
}
```

### 4. Patrocinador do Cliente
**GET** `/customer/sponsor`

**Acesso:** Cliente autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "sponsor": {
    "id": 2,
    "uuid": "550e8400-e29b-41d4-a716-446655440002",
    "name": "Patrocinador",
    "username": "patrocinador",
    "email": "patrocinador@email.com",
    "created_at": "2025-01-01T00:00:00.000000Z"
  }
}
```

### 5. Estatísticas do Cliente
**GET** `/customer/statistics`

**Acesso:** Cliente autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "statistics": {
    "total_orders": 5,
    "total_spent": 250.00,
    "total_tickets": 150,
    "active_tickets": 100,
    "total_commissions": 75.50,
    "total_sponsored": 3,
    "active_sponsored": 2,
    "current_level": 2
  }
}
```

### 6. Rede de Usuário Específico
**GET** `/customer/users/{uuid}/network`

**Acesso:** Cliente autenticado (com verificação de permissão)

**Parâmetros:**
- `uuid` (string): UUID do usuário

**Headers:**
```
Authorization: Bearer {token}
```

### 7. Patrocinador de Usuário Específico
**GET** `/customer/users/{uuid}/sponsor`

**Acesso:** Cliente autenticado (com verificação de permissão)

**Parâmetros:**
- `uuid` (string): UUID do usuário

### 8. Estatísticas de Usuário Específico
**GET** `/customer/users/{uuid}/statistics`

**Acesso:** Cliente autenticado (com verificação de permissão)

**Parâmetros:**
- `uuid` (string): UUID do usuário

---

## Planos (Customer)

### 1. Listar Planos Ativos
**GET** `/customer/plans`

**Acesso:** Cliente autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Opcionais):**
- `promotional` (boolean): Filtrar apenas planos promocionais
- `min_price` (float): Preço mínimo
- `max_price` (float): Preço máximo
- `sort_by` (string): Campo para ordenação (padrão: price)
- `sort_order` (string): asc|desc (padrão: asc)

**Exemplo de URL:**
```
/customer/plans?promotional=true&min_price=50&max_price=200&sort_by=price&sort_order=asc
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Planos listados com sucesso",
  "data": {
    "plans": [
      {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440003",
        "name": "Plano Bronze",
        "description": "Plano básico com 10 tickets",
        "price": 50.00,
        "grant_tickets": 10,
        "status": "active",
        "plan_type": "public",
        "ticket_level": 1,
        "commission_level_1": 10.00,
        "commission_level_2": 5.00,
        "commission_level_3": 2.00,
        "is_promotional": false,
        "max_users": 0,
        "overlap": 1,
        "start_date": "2025-01-01",
        "end_date": "2025-12-31"
      }
    ],
    "total": 1,
    "per_page": 15,
    "current_page": 1
  }
}
```

### 2. Detalhes de um Plano
**GET** `/customer/plans/{uuid}`

**Acesso:** Cliente autenticado

**Parâmetros:**
- `uuid` (string): UUID do plano

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Plano encontrado com sucesso",
  "data": {
    "plan": {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440003",
      "name": "Plano Bronze",
      "description": "Plano básico com 10 tickets",
      "price": 50.00,
      "grant_tickets": 10,
      "status": "active",
      "plan_type": "public",
      "ticket_level": 1,
      "commission_level_1": 10.00,
      "commission_level_2": 5.00,
      "commission_level_3": 2.00,
      "is_promotional": false,
      "max_users": 0,
      "overlap": 1,
      "start_date": "2025-01-01",
      "end_date": "2025-12-31",
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2025-01-01T00:00:00.000000Z"
    }
  }
}
```

### 3. Planos Promocionais
**GET** `/customer/plans/promotional/list`

**Acesso:** Cliente autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Planos promocionais listados com sucesso",
  "data": {
    "plans": [
      {
        "id": 2,
        "uuid": "550e8400-e29b-41d4-a716-446655440004",
        "name": "Plano Promocional",
        "description": "Oferta especial",
        "price": 30.00,
        "grant_tickets": 15,
        "is_promotional": true
      }
    ]
  }
}
```

### 4. Buscar Planos
**GET** `/customer/plans/search`

**Acesso:** Cliente autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Opcionais):**
- `search` (string): Busca por nome ou descrição
- `price_range` (string): Faixa de preço (ex: "50-100")

**Exemplo de URL:**
```
/customer/plans/search?search=bronze&price_range=40-80
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Busca realizada com sucesso",
  "data": {
    "plans": [
      {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440003",
        "name": "Plano Bronze",
        "description": "Plano básico com 10 tickets",
        "price": 50.00
      }
    ],
    "search_terms": "bronze",
    "price_range": "40-80"
  }
}
```

---

## Carrinho (Customer)

### 1. Adicionar Item ao Carrinho
**POST** `/customer/cart/add`

**Acesso:** Cliente autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Payload:**
```json
{
  "plan_uuid": "550e8400-e29b-41d4-a716-446655440003"
}
```

**Campos Obrigatórios:**
- `plan_uuid` (string, uuid): UUID do plano

**Regra de Negócio:** Usuário pode ter apenas 1 item não pago no carrinho

**Resposta de Sucesso (201 - Criado / 200 - Atualizado):**
```json
{
  "success": true,
  "message": "Item adicionado ao carrinho com sucesso",
  "data": {
    "action": "created",
    "cart": {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440005",
      "user_id": 1,
      "plan_id": 1,
      "status": "pending",
      "total_tickets": 10,
      "total_amount": 50.00,
      "plan": {
        "id": 1,
        "name": "Plano Bronze",
        "price": 50.00,
        "grant_tickets": 10
      }
    }
  }
}
```

### 2. Visualizar Carrinho
**GET** `/customer/cart`

**Acesso:** Cliente autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Carrinho carregado com sucesso",
  "data": {
    "cart": {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440005",
      "user_id": 1,
      "plan_id": 1,
      "status": "pending",
      "total_tickets": 10,
      "total_amount": 50.00,
      "plan": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440003",
        "name": "Plano Bronze",
        "description": "Plano básico com 10 tickets",
        "price": 50.00,
        "grant_tickets": 10
      },
      "created_at": "2025-01-01T00:00:00.000000Z"
    }
  }
}
```

**Resposta quando carrinho vazio (200):**
```json
{
  "success": true,
  "message": "Carrinho vazio",
  "data": {
    "cart": null
  }
}
```

### 3. Remover Item do Carrinho
**DELETE** `/customer/cart/remove`

**Acesso:** Cliente autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Item removido do carrinho com sucesso"
}
```

### 4. Limpar Carrinho
**DELETE** `/customer/cart/clear`

**Acesso:** Cliente autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Carrinho limpo com sucesso",
  "data": {
    "cleared_items": 1
  }
}
```

### 5. Finalizar Compra (Checkout)
**POST** `/customer/cart/checkout`

**Acesso:** Cliente autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (201):**
```json
{
  "success": true,
  "message": "Compra finalizada com sucesso",
  "data": {
    "order": {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440006",
      "user_id": 1,
      "plan_id": 1,
      "total_amount": 50.00,
      "status": "pending_payment",
      "created_at": "2025-01-01T00:00:00.000000Z"
    },
    "cart_status": "completed"
  }
}
```

---

## Wallet (Customer)

> **Prefixo:** `/customer/wallet`  
> **Middleware:** `auth:sanctum`  
> **Acesso:** Cliente autenticado

### Sistema de Wallet

Cada usuário possui uma carteira digital (wallet) com saldo em reais que pode ser usado para aplicar em rifas e outras operações do sistema.

**Funcionalidades:**
- **Saldo Total**: Valor total disponível na wallet
- **Saldo Bloqueado**: Valor reservado/bloqueado temporariamente
- **Saldo Disponível**: Saldo total - saldo bloqueado
- **Histórico**: Todas as transações (créditos e débitos)
- **Extratos**: Filtros avançados por tipo, origem e período

### 1. Visualizar Wallet Completa
**GET** `/customer/wallet`

**Acesso:** Cliente autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Wallet carregada com sucesso",
  "data": {
    "wallet": {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440100",
      "balance": 149.50,
      "blocked": 0.00,
      "available_balance": 149.50,
      "withdrawals": 0.00,
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2025-01-20T10:30:00.000000Z"
    },
    "statistics": {
      "total_credits": 200.00,
      "total_debits": 50.50,
      "net_balance": 149.50
    },
    "recent_transactions": [
      {
        "id": 5,
        "uuid": "abc-123-def",
        "amount": 2.00,
        "type": "debit",
        "description": "Aplicação em rifa: iPhone 15 Pro Max - 200 tickets",
        "origin": "raffle",
        "created_at": "2025-01-20T10:00:00.000000Z"
      },
      {
        "id": 4,
        "uuid": "abc-123-xyz",
        "amount": 50.00,
        "type": "credit",
        "description": "Crédito de plano: Plano Bronze",
        "origin": "order",
        "created_at": "2025-01-15T14:30:00.000000Z"
      }
    ]
  }
}
```

**Descrição:**
Retorna informações completas da wallet incluindo saldo, estatísticas e as últimas 5 transações.

---

### 2. Consultar Apenas o Saldo
**GET** `/customer/wallet/balance`

**Acesso:** Cliente autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Saldo carregado com sucesso",
  "data": {
    "balance": 149.50,
    "blocked": 0.00,
    "available_balance": 149.50,
    "withdrawals": 0.00
  }
}
```

**Descrição:**
Endpoint simplificado para consultar apenas os valores de saldo sem histórico.

---

### 3. Extratos Financeiros
**GET** `/customer/wallet/statements`

**Acesso:** Cliente autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Opcionais):**
- `type` (string): Filtrar por tipo - `credit` ou `debit`
- `origin` (string): Filtrar por origem - `order`, `raffle`, `commission`, `withdrawal`, etc
- `date_from` (date): Data inicial - formato YYYY-MM-DD
- `date_to` (date): Data final - formato YYYY-MM-DD
- `per_page` (int): Itens por página (padrão: 15)
- `page` (int): Página atual (padrão: 1)

**Exemplo de URL:**
```
/customer/wallet/statements?type=credit&date_from=2025-01-01&date_to=2025-01-31&per_page=20
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Extratos carregados com sucesso",
  "data": {
    "statements": [
      {
        "id": 5,
        "uuid": "abc-123-def",
        "correlation_id": "raffle-apply-123",
        "amount": 2.00,
        "type": "debit",
        "description": "Aplicação em rifa: iPhone 15 Pro Max - 200 tickets",
        "origin": "raffle",
        "created_at": "2025-01-20T10:00:00.000000Z"
      },
      {
        "id": 4,
        "uuid": "abc-123-xyz",
        "correlation_id": "order-456",
        "amount": 50.00,
        "type": "credit",
        "description": "Crédito de plano: Plano Bronze",
        "origin": "order",
        "created_at": "2025-01-15T14:30:00.000000Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 25,
      "last_page": 2
    },
    "summary": {
      "total_credits": 200.00,
      "total_debits": 50.50,
      "net_balance": 149.50
    },
    "filters": {
      "type": "credit",
      "origin": null,
      "date_from": "2025-01-01",
      "date_to": "2025-01-31"
    }
  }
}
```

**Descrição:**
Lista todos os extratos financeiros (créditos e débitos) com filtros avançados por tipo, origem e período. Inclui totais do período filtrado.

**Tipos de Transação:**
- `credit`: Entrada de dinheiro na wallet
- `debit`: Saída de dinheiro da wallet

**Origens Possíveis:**
- `order`: Compra de plano
- `raffle`: Aplicação em rifa
- `commission`: Comissão recebida
- `withdrawal`: Saque realizado
- `refund`: Reembolso
- `adjustment`: Ajuste manual (admin)

---

### 4. Histórico de Transações com Analytics
**GET** `/customer/wallet/transactions`

**Acesso:** Cliente autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Opcionais):**
- `date_from` (date): Data inicial - formato YYYY-MM-DD
- `date_to` (date): Data final - formato YYYY-MM-DD
- `per_page` (int): Itens por página (padrão: 15)
- `page` (int): Página atual (padrão: 1)

**Exemplo de URL:**
```
/customer/wallet/transactions?date_from=2025-01-01&date_to=2025-01-31
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Histórico de transações carregado com sucesso",
  "data": {
    "transactions": [
      {
        "id": 5,
        "uuid": "abc-123-def",
        "correlation_id": "raffle-apply-123",
        "amount": 2.00,
        "type": "debit",
        "description": "Aplicação em rifa: iPhone 15 Pro Max - 200 tickets",
        "origin": "raffle",
        "created_at": "2025-01-20T10:00:00.000000Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 25,
      "last_page": 2
    },
    "analytics": {
      "by_type": {
        "credit": {
          "count": 10,
          "total": 200.00
        },
        "debit": {
          "count": 15,
          "total": 50.50
        }
      },
      "by_origin": [
        {
          "origin": "order",
          "credits": {
            "count": 5,
            "total": 150.00
          },
          "debits": {
            "count": 0,
            "total": 0.00
          }
        },
        {
          "origin": "raffle",
          "credits": {
            "count": 0,
            "total": 0.00
          },
          "debits": {
            "count": 10,
            "total": 30.00
          }
        },
        {
          "origin": "commission",
          "credits": {
            "count": 5,
            "total": 50.00
          },
          "debits": {
            "count": 0,
            "total": 0.00
          }
        }
      ]
    },
    "filters": {
      "date_from": "2025-01-01",
      "date_to": "2025-01-31"
    }
  }
}
```

**Descrição:**
Lista o histórico completo de transações com analytics agrupadas por tipo e origem. Útil para visualização de gráficos e relatórios.

**Analytics Incluídas:**
- **by_type**: Totais agrupados por crédito/débito
- **by_origin**: Totais agrupados por origem da transação

---

## Orders (Customer)

> **Prefixo:** `/customer/orders`  
> **Middleware:** `auth:sanctum`  
> **Acesso:** Cliente autenticado

### Sistema de Orders

Orders (pedidos/compras) representam as compras de planos realizadas pelos usuários. Cada order registra detalhes da transação, status de pagamento, e metadados do plano adquirido.

### 1. Listar Minhas Compras
**GET** `/customer/orders`

**Acesso:** Cliente autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Opcionais):**
- `status` (string): Filtrar por status - `pending`, `approved`, `rejected`, `cancelled`
- `date_from` (date): Data inicial - formato YYYY-MM-DD
- `date_to` (date): Data final - formato YYYY-MM-DD
- `per_page` (int): Itens por página (padrão: 15)
- `page` (int): Página atual (padrão: 1)

**Exemplo de URL:**
```
/customer/orders?status=approved&date_from=2025-01-01&date_to=2025-01-31
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Compras carregadas com sucesso",
  "data": {
    "orders": [
      {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440001",
        "plan": {
          "id": 1,
          "uuid": "550e8400-e29b-41d4-a716-446655440002",
          "name": "Plano Bronze",
          "price": 50.00
        },
        "amount": 50.00,
        "currency": "BRL",
        "status": "approved",
        "payment_method": "credit_card",
        "paid_at": "2025-01-15T14:30:00.000000Z",
        "created_at": "2025-01-15T14:25:00.000000Z",
        "updated_at": "2025-01-15T14:30:00.000000Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 5,
      "last_page": 1
    },
    "statistics": {
      "total_orders": 5,
      "total_approved": 4,
      "total_pending": 1,
      "total_amount": 200.00
    },
    "filters": {
      "status": "approved",
      "date_from": "2025-01-01",
      "date_to": "2025-01-31"
    }
  }
}
```

**Descrição:**
Lista todas as compras do usuário autenticado com filtros opcionais e estatísticas.

**Status Possíveis:**
- `pending`: Aguardando pagamento
- `approved`: Pagamento aprovado
- `rejected`: Pagamento rejeitado
- `cancelled`: Pedido cancelado

---

### 2. Detalhes de uma Compra
**GET** `/customer/orders/{uuid}`

**Acesso:** Cliente autenticado

**Parâmetros:**
- `uuid` (string): UUID da order

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Detalhes da compra carregados com sucesso",
  "data": {
    "order": {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440001",
      "user_metadata": {
        "name": "João Silva",
        "email": "joao@email.com"
      },
      "plan": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440002",
        "name": "Plano Bronze",
        "description": "Plano básico com benefícios essenciais",
        "price": 50.00,
        "type": "monthly",
        "metadata": {
          "benefits": ["Acesso a rifas", "Comissões"],
          "duration_days": 30
        }
      },
      "amount": 50.00,
      "currency": "BRL",
      "status": "approved",
      "payment_method": "credit_card",
      "payment_details": {
        "card_brand": "visa",
        "last_digits": "1234",
        "installments": 1
      },
      "paid_at": "2025-01-15T14:30:00.000000Z",
      "cart": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440003",
        "status": "completed"
      },
      "created_at": "2025-01-15T14:25:00.000000Z",
      "updated_at": "2025-01-15T14:30:00.000000Z"
    }
  }
}
```

**Resposta de Erro (404):**
```json
{
  "success": false,
  "message": "Compra não encontrada"
}
```

**Descrição:**
Retorna detalhes completos de uma compra específica do usuário. Inclui informações do plano adquirido, metadados do pagamento, e carrinho associado.

**Validações:**
- Usuário só pode visualizar suas próprias compras
- Order deve pertencer ao usuário autenticado

---

## Rifas e Aplicações (Customer)

> **Prefixo:** `/customer`  
> **Middleware:** `auth:sanctum`  
> **Acesso:** Cliente autenticado

### Sistema de Aplicação em Rifas

O sistema de rifas foi modernizado para usar **saldo de wallet** ao invés de tickets individuais. Quando um usuário compra um plano, ele recebe crédito na sua wallet que pode ser usado para aplicar em rifas.

**Arquitetura:**
- **Wallet**: Cada usuário possui uma wallet com saldo em reais
- **Aplicação**: Usuário paga com saldo da wallet para participar de rifas
- **Tickets de Rifa**: Números sorteados automaticamente do pool de tickets disponíveis
- **Financial Statements**: Todas as transações são registradas para auditoria

### 1. Listar Rifas Disponíveis
**GET** `/customer/raffles`

**Acesso:** Cliente autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Opcionais):**
- `page` (int): Página atual (padrão: 1)
- `per_page` (int): Itens por página (padrão: 15)
- `status` (string): Filtrar por status (active, pending, completed, cancelled, inactive)

**Resposta de Sucesso (200):**
```json
{
  "raffles": {
    "data": [
      {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440010",
        "title": "iPhone 15 Pro Max",
        "description": "iPhone 15 Pro Max 256GB Azul Titânio",
        "prize_value": 8999.00,
        "unit_ticket_value": 0.01,
        "min_tickets_required": 200,
        "status": "active",
        "draw_date": "2025-12-31T20:00:00.000000Z",
        "created_at": "2025-01-01T00:00:00.000000Z"
      }
    ],
    "current_page": 1,
    "per_page": 15,
    "total": 5,
    "last_page": 1
  }
}
```

**Descrição:**
Lista todas as rifas disponíveis para participação. Por padrão, mostra apenas rifas com status "active".

---

### 2. Detalhes de uma Rifa
**GET** `/customer/raffles/{uuid}`

**Acesso:** Cliente autenticado

**Parâmetros:**
- `uuid` (string): UUID da rifa

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "raffle": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440010",
    "title": "iPhone 15 Pro Max",
    "description": "iPhone 15 Pro Max 256GB Azul Titânio",
    "prize_value": 8999.00,
    "operation_cost": 899.00,
    "unit_ticket_value": 0.01,
    "min_tickets_required": 200,
    "liquidity_ratio": 85.0,
    "liquid_value": 7649.15,
    "status": "active",
    "notes": "Sorteio ao vivo no Instagram",
    "draw_date": "2025-12-31T20:00:00.000000Z",
    "created_at": "2025-01-01T00:00:00.000000Z",
    "updated_at": "2025-01-01T00:00:00.000000Z"
  }
}
```

**Resposta de Erro (404):**
```json
{
  "message": "Rifa não encontrada"
}
```

**Descrição:**
Retorna detalhes completos de uma rifa específica, incluindo valor do prêmio, custo de operação, e valor líquido.

---

### 3. Aplicar em uma Rifa
**POST** `/customer/raffles/{uuid}/apply`

**Acesso:** Cliente autenticado

**Parâmetros:**
- `uuid` (string): UUID da rifa

**Headers:**
```
Authorization: Bearer {token}
```

**Payload:**
```json
{
  "quantity": 200
}
```

**Campos Obrigatórios:**
- `quantity` (integer, min:1): Quantidade de tickets a aplicar (mínimo: min_tickets_required da rifa)

**Validações:**
- Usuário deve ter saldo suficiente na wallet
- Rifa deve estar com status "active"
- Quantidade deve ser >= min_tickets_required da rifa
- Usuário não pode aplicar mais de uma vez na mesma rifa
- Deve haver tickets disponíveis no pool

**Resposta de Sucesso (201):**
```json
{
  "success": true,
  "message": "Aplicação realizada com sucesso",
  "user_id": 1,
  "raffle_id": 1,
  "raffle_title": "iPhone 15 Pro Max",
  "tickets_count": 200,
  "total_cost": 2.00,
  "ticket_numbers": [
    "0000001",
    "0000002",
    "0000003",
    "..."
  ],
  "remaining_balance": 147.50,
  "duration_ms": 5523.25
}
```

**Respostas de Erro:**

**400 - Saldo Insuficiente:**
```json
{
  "success": false,
  "message": "Saldo insuficiente. Necessário: R$ 2.00, Disponível: R$ 1.50",
  "user_id": 1,
  "raffle_id": 1
}
```

**400 - Já Aplicou:**
```json
{
  "success": false,
  "message": "Usuário já aplicou nesta rifa",
  "user_id": 1,
  "raffle_id": 1
}
```

**400 - Rifa Inativa:**
```json
{
  "success": false,
  "message": "Esta rifa não está ativa",
  "user_id": 1,
  "raffle_id": 1
}
```

**400 - Quantidade Insuficiente:**
```json
{
  "success": false,
  "message": "Quantidade mínima de tickets: 200",
  "user_id": 1,
  "raffle_id": 1
}
```

**404 - Rifa Não Encontrada:**
```json
{
  "message": "Rifa não encontrada"
}
```

**422 - Validação:**
```json
{
  "message": "The quantity field is required.",
  "errors": {
    "quantity": ["The quantity field is required."]
  }
}
```

**Descrição:**
Aplica tickets em uma rifa usando o saldo da wallet do usuário. O sistema:
1. Valida se o usuário tem saldo suficiente
2. Debita o valor total da wallet
3. Cria registro de débito no Financial Statement
4. Sorteia tickets aleatórios do pool disponível
5. Cria registros na tabela raffle_tickets com status "confirmed"
6. Retorna os números dos tickets sorteados

**Processo Assíncrono:**
A aplicação pode ser processada via Job Queue para melhor performance:
- Job: `UserApplyToRaffleJob`
- Fila: `raffle-applications`
- Retry: 3 tentativas com 5 segundos de intervalo
- Timeout: 120 segundos

**Regras de Negócio:**
1. Usuário pode aplicar apenas UMA vez por rifa
2. Sempre aplica a quantidade mínima (min_tickets_required)
3. Tickets são sorteados aleatoriamente do pool disponível
4. Operação é transacional (rollback em caso de erro)
5. Financial Statement registra débito com descrição detalhada
6. Saldo da wallet é atualizado imediatamente

---

### 4. Listar Minhas Aplicações
**GET** `/customer/raffles/my-applications`

**Acesso:** Cliente autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Opcionais):**
- `page` (int): Página atual (padrão: 1)
- `per_page` (int): Itens por página (padrão: 15)
- `status` (string): Filtrar por status da rifa

**Resposta de Sucesso (200):**
```json
{
  "applications": {
    "data": [
      {
        "raffle": {
          "id": 1,
          "uuid": "550e8400-e29b-41d4-a716-446655440010",
          "title": "iPhone 15 Pro Max",
          "status": "active"
        },
        "tickets_count": 200,
        "total_paid": 2.00,
        "ticket_numbers": ["0000001", "0000002", "..."],
        "applied_at": "2025-01-20T10:00:00.000000Z"
      }
    ],
    "current_page": 1,
    "per_page": 15,
    "total": 3
  }
}
```

**Descrição:**
Lista todas as rifas em que o usuário já aplicou, mostrando quantidade de tickets e valores pagos.

---

### 5. Meus Tickets em uma Rifa
**GET** `/customer/raffles/{uuid}/my-tickets`

**Acesso:** Cliente autenticado

**Parâmetros:**
- `uuid` (string): UUID da rifa

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "raffle": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440010",
    "title": "iPhone 15 Pro Max",
    "status": "active"
  },
  "tickets": [
    {
      "id": 1,
      "uuid": "abc123-def456-789",
      "ticket_number": "0000001",
      "status": "confirmed",
      "created_at": "2025-01-20T10:00:00.000000Z"
    },
    {
      "id": 2,
      "uuid": "abc123-def456-790",
      "ticket_number": "0000002",
      "status": "confirmed",
      "created_at": "2025-01-20T10:00:00.000000Z"
    }
  ],
  "total": 200,
  "by_status": {
    "confirmed": 200,
    "winner": 0
  }
}
```

**Resposta de Erro (404):**
```json
{
  "message": "Você não possui tickets nesta rifa"
}
```

**Descrição:**
Lista todos os tickets do usuário em uma rifa específica, incluindo breakdown por status.

**Status Possíveis:**
- `confirmed`: Ticket confirmado para o sorteio
- `winner`: Ticket vencedor do sorteio

---

## Endpoints para Administradores

> **Prefixo:** `/administrator`  
> **Middleware:** `auth:sanctum`, `admin`  
> **Acesso:** Apenas usuários com role = 'admin'

### Gestão de Usuários

### 1. Listar Usuários
**GET** `/administrator/users`

**Acesso:** Administrador

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Opcionais):**
- `status` (string): active|inactive|suspended
- `role` (string): user|admin
- `search` (string): Busca por nome, email ou username
- `per_page` (int): Itens por página (padrão: 15)
- `page` (int): Página atual

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Usuários listados com sucesso",
  "data": {
    "users": [
      {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "name": "João Silva",
        "email": "joao@email.com",
        "username": "joaosilva",
        "role": "user",
        "status": "active",
        "sponsor_id": null,
        "created_at": "2025-01-01T00:00:00.000000Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 25,
      "last_page": 2
    }
  }
}
```

### 2. Detalhes de um Usuário
**GET** `/administrator/users/{uuid}`

**Acesso:** Administrador

**Parâmetros:**
- `uuid` (string): UUID do usuário

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Usuário encontrado com sucesso",
  "data": {
    "user": {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "name": "João Silva",
      "email": "joao@email.com",
      "username": "joaosilva",
      "phone": "11999999999",
      "role": "user",
      "status": "active",
      "sponsor_id": 2,
      "sponsor": {
        "id": 2,
        "name": "Patrocinador",
        "username": "patrocinador"
      },
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2025-01-01T00:00:00.000000Z"
    }
  }
}
```

### 3. Criar Usuário
**POST** `/administrator/users`

**Acesso:** Administrador

**Headers:**
```
Authorization: Bearer {token}
```

**Payload:**
```json
{
  "name": "Novo Usuário",
  "email": "novo@email.com",
  "username": "novousuario",
  "password": "senha123456",
  "password_confirmation": "senha123456",
  "phone": "11988887777",
  "role": "user",
  "status": "active",
  "sponsor": "username_patrocinador"
}
```

**Campos Obrigatórios:**
- `name` (string, max:255)
- `email` (string, email, único)
- `username` (string, max:255, único)
- `password` (string, min:8, confirmed)

**Campos Opcionais:**
- `phone` (string, max:20)
- `role` (string): user|admin (padrão: user)
- `status` (string): active|inactive|suspended (padrão: active)
- `sponsor` (string): username do patrocinador

### 4. Atualizar Usuário
**PUT** `/administrator/users/{uuid}`

**Acesso:** Administrador

**Parâmetros:**
- `uuid` (string): UUID do usuário

**Headers:**
```
Authorization: Bearer {token}
```

**Payload:**
```json
{
  "name": "Nome Atualizado",
  "email": "atualizado@email.com",
  "phone": "11777777777",
  "role": "admin",
  "status": "inactive"
}
```

**Campos Opcionais (todos):**
- `name` (string, max:255)
- `email` (string, email, único exceto próprio)
- `username` (string, max:255, único exceto próprio)
- `phone` (string, max:20)
- `role` (string): user|admin
- `status` (string): active|inactive|suspended

### 5. Excluir Usuário
**DELETE** `/administrator/users/{uuid}`

**Acesso:** Administrador

**Parâmetros:**
- `uuid` (string): UUID do usuário

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Usuário excluído com sucesso"
}
```

### 6. Rede de um Usuário
**GET** `/administrator/users/{uuid}/network`

**Acesso:** Administrador

**Parâmetros:**
- `uuid` (string): UUID do usuário

### 7. Patrocinador de um Usuário
**GET** `/administrator/users/{uuid}/sponsor`

**Acesso:** Administrador

**Parâmetros:**
- `uuid` (string): UUID do usuário

### 8. Estatísticas de um Usuário
**GET** `/administrator/users/{uuid}/statistics`

**Acesso:** Administrador

**Parâmetros:**
- `uuid` (string): UUID do usuário

### 9. Estatísticas do Sistema
**GET** `/administrator/statistics`

**Acesso:** Administrador

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "data": {
    "total_users": 150,
    "active_users": 120,
    "total_orders": 75,
    "total_revenue": 15000.00,
    "total_commissions": 2250.00,
    "total_plans": 10,
    "active_plans": 8
  }
}
```

### 10. Dashboard
**GET** `/administrator/dashboard`

**Acesso:** Administrador

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "data": {
    "overview": {
      "total_users": 150,
      "new_users_today": 5,
      "total_orders": 75,
      "orders_today": 3,
      "total_revenue": 15000.00,
      "revenue_today": 450.00
    },
    "recent_orders": [],
    "recent_users": [],
    "top_plans": []
  }
}
```

### 11. Atualização em Massa
**POST** `/administrator/users/bulk-update`

**Acesso:** Administrador

**Headers:**
```
Authorization: Bearer {token}
```

**Payload:**
```json
{
  "user_uuids": [
    "550e8400-e29b-41d4-a716-446655440000",
    "550e8400-e29b-41d4-a716-446655440001"
  ],
  "updates": {
    "status": "inactive",
    "role": "user"
  }
}
```

### 12. Exclusão em Massa
**POST** `/administrator/users/bulk-delete`

**Acesso:** Administrador

**Headers:**
```
Authorization: Bearer {token}
```

**Payload:**
```json
{
  "user_uuids": [
    "550e8400-e29b-41d4-a716-446655440000",
    "550e8400-e29b-41d4-a716-446655440001"
  ]
}
```

### 13. Exportar Usuários
**POST** `/administrator/users/export`

**Acesso:** Administrador

**Headers:**
```
Authorization: Bearer {token}
```

**Payload:**
```json
{
  "format": "csv",
  "filters": {
    "status": "active",
    "role": "user"
  }
}
```

---

## Gestão de Planos (Administrator)

### 1. Listar Planos (Admin)
**GET** `/administrator/plans`

**Acesso:** Administrador

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Opcionais):**
- `status` (string): active|inactive|archived
- `promotional` (boolean): true|false
- `min_price` (float): Preço mínimo
- `max_price` (float): Preço máximo
- `search` (string): Busca por nome ou descrição
- `sort_by` (string): Campo para ordenação (padrão: created_at)
- `sort_order` (string): asc|desc (padrão: desc)
- `per_page` (int): Itens por página (padrão: 15)

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Planos listados com sucesso",
  "data": {
    "plans": [
      {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440003",
        "name": "Plano Bronze",
        "description": "Plano básico com 10 tickets",
        "price": 50.00,
        "grant_tickets": 10,
        "status": "active",
        "plan_type": "public",
        "ticket_level": 1,
        "commission_level_1": 10.00,
        "commission_level_2": 5.00,
        "commission_level_3": 2.00,
        "is_promotional": false,
        "max_users": 0,
        "overlap": 1,
        "start_date": "2025-01-01",
        "end_date": "2025-12-31",
        "created_at": "2025-01-01T00:00:00.000000Z",
        "updated_at": "2025-01-01T00:00:00.000000Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 5,
      "last_page": 1
    }
  }
}
```

### 2. Detalhes de um Plano (Admin)
**GET** `/administrator/plans/{uuid}`

**Acesso:** Administrador

**Parâmetros:**
- `uuid` (string): UUID do plano

**Headers:**
```
Authorization: Bearer {token}
```

### 3. Criar Plano
**POST** `/administrator/plans`

**Acesso:** Administrador

**Headers:**
```
Authorization: Bearer {token}
```

**Payload:**
```json
{
  "name": "Plano Ouro",
  "description": "Plano premium com 50 tickets",
  "price": 200.00,
  "grant_tickets": 50,
  "status": "active",
  "plan_type": "public",
  "commission_level_1": 15.00,
  "commission_level_2": 8.00,
  "commission_level_3": 3.00,
  "is_promotional": false,
  "overlap": 1,
  "start_date": "2025-01-01",
  "end_date": "2025-12-31"
}
```

**Campos Obrigatórios:**
- `name` (string, max:255, único)
- `description` (string, max:1000)
- `price` (numeric, min:0)
- `grant_tickets` (integer, min:0)
- `status` (string): active|inactive|archived
- `commission_level_1` (numeric, min:0, max:100)
- `commission_level_2` (numeric, min:0, max:100)
- `commission_level_3` (numeric, min:0, max:100)
- `overlap` (integer, min:0)
- `start_date` (date)

**Campos Opcionais:**
- `plan_type` (string): public|private (padrão: public)
- `is_promotional` (boolean, padrão: false)
- `end_date` (date, deve ser depois de start_date)

**Resposta de Sucesso (201):**
```json
{
  "success": true,
  "message": "Plano criado com sucesso",
  "data": {
    "plan": {
      "id": 2,
      "uuid": "550e8400-e29b-41d4-a716-446655440007",
      "name": "Plano Ouro",
      "description": "Plano premium com 50 tickets",
      "price": 200.00,
      "grant_tickets": 50,
      "status": "active",
      "plan_type": "public",
      "created_at": "2025-01-01T00:00:00.000000Z"
    }
  }
}
```

### 4. Atualizar Plano
**PUT** `/administrator/plans/{uuid}`

**Acesso:** Administrador

**Parâmetros:**
- `uuid` (string): UUID do plano

**Headers:**
```
Authorization: Bearer {token}
```

**Payload (todos os campos opcionais):**
```json
{
  "name": "Plano Ouro Premium",
  "description": "Plano premium atualizado",
  "price": 250.00,
  "grant_tickets": 60,
  "status": "active",
  "commission_level_1": 18.00
}
```

**Campos Opcionais:**
- `name` (string, max:255, único exceto próprio)
- `description` (string, max:1000)
- `price` (numeric, min:0)
- `grant_tickets` (integer, min:0)
- `status` (string): active|inactive|archived
- `commission_level_1` (numeric, min:0, max:100)
- `commission_level_2` (numeric, min:0, max:100)
- `commission_level_3` (numeric, min:0, max:100)
- `is_promotional` (boolean)
- `overlap` (integer, min:0)
- `start_date` (date)
- `end_date` (date, deve ser depois de start_date)

### 5. Excluir Plano
**DELETE** `/administrator/plans/{uuid}`

**Acesso:** Administrador

**Parâmetros:**
- `uuid` (string): UUID do plano

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Plano excluído com sucesso"
}
```

### 6. Alternar Status do Plano
**POST** `/administrator/plans/{uuid}/toggle-status`

**Acesso:** Administrador

**Parâmetros:**
- `uuid` (string): UUID do plano

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Status do plano alterado com sucesso",
  "data": {
    "plan": {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440003",
      "name": "Plano Bronze",
      "status": "inactive"
    }
  }
}
```

### 7. Estatísticas de Planos
**GET** `/administrator/plans/statistics/overview`

**Acesso:** Administrador

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "data": {
    "total_plans": 10,
    "active_plans": 8,
    "inactive_plans": 2,
    "promotional_plans": 3,
    "total_revenue": 15000.00,
    "most_popular_plan": {
      "id": 1,
      "name": "Plano Bronze",
      "total_orders": 25
    }
  }
}
```

---

## Gestão de Orders (Administrator)

> **Prefixo:** `/administrator/orders`  
> **Middleware:** `auth:sanctum`, `admin`  
> **Acesso:** Apenas administradores

### Sistema de Orders (Admin)

Permite aos administradores visualizar e gerenciar todas as compras (orders) do sistema com filtros avançados e estatísticas completas.

### 1. Listar Todas as Orders
**GET** `/administrator/orders`

**Acesso:** Administrador

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Opcionais):**
- `status` (string): Filtrar por status - `pending`, `approved`, `rejected`, `cancelled`
- `user_id` (int): Filtrar por ID do usuário
- `plan_id` (int): Filtrar por ID do plano
- `date_from` (date): Data inicial - formato YYYY-MM-DD
- `date_to` (date): Data final - formato YYYY-MM-DD
- `search` (string): Buscar por UUID da order, email/nome do usuário, ou nome do plano
- `per_page` (int): Itens por página (padrão: 15)
- `page` (int): Página atual (padrão: 1)

**Exemplo de URL:**
```
/administrator/orders?status=approved&user_id=5&date_from=2025-01-01&search=bronze
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Orders carregadas com sucesso",
  "data": {
    "orders": [
      {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440001",
        "user": {
          "id": 5,
          "uuid": "550e8400-e29b-41d4-a716-446655440010",
          "name": "João Silva",
          "email": "joao@email.com"
        },
        "plan": {
          "id": 1,
          "uuid": "550e8400-e29b-41d4-a716-446655440002",
          "name": "Plano Bronze",
          "price": 50.00
        },
        "amount": 50.00,
        "currency": "BRL",
        "status": "approved",
        "payment_method": "credit_card",
        "paid_at": "2025-01-15T14:30:00.000000Z",
        "created_at": "2025-01-15T14:25:00.000000Z",
        "updated_at": "2025-01-15T14:30:00.000000Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 125,
      "last_page": 9
    },
    "statistics": {
      "total_orders": 125,
      "total_approved": 100,
      "total_pending": 15,
      "total_rejected": 5,
      "total_cancelled": 5,
      "total_revenue": 5000.00
    },
    "filters": {
      "status": "approved",
      "user_id": 5,
      "plan_id": null,
      "date_from": "2025-01-01",
      "date_to": null,
      "search": "bronze"
    }
  }
}
```

**Descrição:**
Lista todas as orders do sistema com filtros avançados por status, usuário, plano, período e busca textual. Inclui estatísticas completas.

**Funcionalidades de Busca:**
- **Por UUID**: Busca exata pelo UUID da order
- **Por Usuário**: Busca por email ou nome do usuário (LIKE)
- **Por Plano**: Busca por nome do plano (LIKE)

**Estatísticas Incluídas:**
- `total_orders`: Total de orders no sistema
- `total_approved`: Orders com pagamento aprovado
- `total_pending`: Orders aguardando pagamento
- `total_rejected`: Orders com pagamento rejeitado
- `total_cancelled`: Orders canceladas
- `total_revenue`: Receita total (soma de orders aprovadas)

**Status Possíveis:**
- `pending`: Aguardando pagamento
- `approved`: Pagamento aprovado
- `rejected`: Pagamento rejeitado
- `cancelled`: Pedido cancelado

---

## Gestão de Rifas (Administrator)

### 1. Listar Rifas
**GET** `/administrator/raffles`

**Acesso:** Administrador

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Opcionais):**
- `status` (string): open|closed|drawn|scheduled
- `min_prize` (float): Valor mínimo do prêmio
- `max_prize` (float): Valor máximo do prêmio
- `search` (string): Busca por título ou descrição
- `sort_by` (string): Campo para ordenação
- `sort_order` (string): asc|desc
- `per_page` (int): Itens por página

### 2. Detalhes de uma Rifa
**GET** `/administrator/raffles/{uuid}`

**Acesso:** Administrador

**Parâmetros:**
- `uuid` (string): UUID da rifa

### 3. Criar Rifa
**POST** `/administrator/raffles`

**Acesso:** Administrador

**Headers:**
```
Authorization: Bearer {token}
```

**Payload:**
```json
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

**Campos Obrigatórios:**
- `title` (string, max:255): Título da rifa
- `prize_value` (numeric, min:0): Valor do prêmio
- `operation_cost` (numeric, min:0): Custo de operação
- `unit_ticket_value` (numeric, min:0): Valor unitário de cada ticket
- `liquidity_ratio` (numeric, min:0, max:100): Percentual de liquidez (padrão: 100%)
- `min_tickets_required` (integer, min:1): Quantidade mínima de tickets por aplicação
- `draw_date` (datetime): Data do sorteio
- `status` (string): pending|active|completed|cancelled|inactive

**Campos Opcionais:**
- `description` (string, max:1000): Descrição detalhada
- `notes` (string, max:1000): Observações internas

### 4. Atualizar Rifa
**PUT** `/administrator/raffles/{uuid}`

**Acesso:** Administrador

**Parâmetros:**
- `uuid` (string): UUID da rifa

### 5. Excluir Rifa
**DELETE** `/administrator/raffles/{uuid}`

**Acesso:** Administrador

**Parâmetros:**
- `uuid` (string): UUID da rifa

### 6. Restaurar Rifa
**POST** `/administrator/raffles/{uuid}/restore`

**Acesso:** Administrador

**Parâmetros:**
- `uuid` (string): UUID da rifa

### 7. Alternar Status da Rifa
**POST** `/administrator/raffles/{uuid}/toggle-status`

**Acesso:** Administrador

**Parâmetros:**
- `uuid` (string): UUID da rifa

### 8. Estatísticas de Rifas
**GET** `/administrator/raffles/statistics/overview`

**Acesso:** Administrador

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "data": {
    "total_raffles": 5,
    "open_raffles": 2,
    "closed_raffles": 1,
    "drawn_raffles": 2,
    "total_prize_value": 50000.00,
    "total_tickets_sold": 1500,
    "total_revenue": 15000.00
  }
}
```

---

## Endpoints Compartilhados

### 1. Health Check
**GET** `/health`

**Acesso:** Público

**Resposta de Sucesso (200):**
```json
{
  "status": "ok",
  "timestamp": "2025-01-01T00:00:00.000000Z",
  "version": "1.0.0",
  "environment": "local",
  "uptime": 0
}
```

### 2. Health Check Detalhado
**GET** `/health/detailed`

**Acesso:** Público

**Resposta de Sucesso (200):**
```json
{
  "status": "healthy",
  "checks": {
    "database": "ok",
    "cache": "ok",
    "queue": "ok"
  },
  "timestamp": "2025-01-01T00:00:00.000000Z"
}
```

### 3. Teste
**GET** `/test`

**Acesso:** Público

**Resposta de Sucesso (200):**
```json
{
  "message": "API funcionando corretamente",
  "timestamp": "2025-01-01T00:00:00.000000Z"
}
```

### 4. Métricas do Usuário
**GET** `/metrics/user`

**Acesso:** Autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Resposta de Sucesso (200):**
```json
{
  "user_id": 1,
  "requests_today": 25,
  "last_activity": "2025-01-01T12:00:00.000000Z"
}
```

---

## Códigos de Status HTTP

### Sucesso
- **200** OK - Operação realizada com sucesso
- **201** Created - Recurso criado com sucesso

### Erro do Cliente
- **400** Bad Request - Requisição inválida
- **401** Unauthorized - Não autenticado
- **403** Forbidden - Não autorizado (sem permissão)
- **404** Not Found - Recurso não encontrado
- **422** Unprocessable Entity - Dados de validação inválidos

### Erro do Servidor
- **500** Internal Server Error - Erro interno do servidor

---

## Modelos de Dados

### User
```json
{
  "id": 1,
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "name": "João Silva",
  "email": "joao@email.com",
  "username": "joaosilva",
  "phone": "11999999999",
  "role": "user",
  "status": "active",
  "sponsor_id": 2,
  "created_at": "2025-01-01T00:00:00.000000Z",
  "updated_at": "2025-01-01T00:00:00.000000Z",
  "deleted_at": null
}
```

### Plan
```json
{
  "id": 1,
  "uuid": "550e8400-e29b-41d4-a716-446655440003",
  "name": "Plano Bronze",
  "description": "Plano básico com 10 tickets",
  "price": 50.00,
  "grant_tickets": 10,
  "status": "active",
  "plan_type": "public",
  "ticket_level": 1,
  "commission_level_1": 10.00,
  "commission_level_2": 5.00,
  "commission_level_3": 2.00,
  "is_promotional": false,
  "max_users": 0,
  "overlap": 1,
  "start_date": "2025-01-01",
  "end_date": "2025-12-31",
  "created_at": "2025-01-01T00:00:00.000000Z",
  "updated_at": "2025-01-01T00:00:00.000000Z",
  "deleted_at": null
}
```

### Cart
```json
{
  "id": 1,
  "uuid": "550e8400-e29b-41d4-a716-446655440005",
  "user_id": 1,
  "plan_id": 1,
  "order_id": null,
  "status": "pending",
  "total_tickets": 10,
  "total_amount": 50.00,
  "created_at": "2025-01-01T00:00:00.000000Z",
  "updated_at": "2025-01-01T00:00:00.000000Z"
}
```

### Order
```json
{
  "id": 1,
  "uuid": "550e8400-e29b-41d4-a716-446655440006",
  "user_id": 1,
  "plan_id": 1,
  "total_amount": 50.00,
  "status": "pending_payment",
  "created_at": "2025-01-01T00:00:00.000000Z",
  "updated_at": "2025-01-01T00:00:00.000000Z"
}
```

### Raffle
```json
{
  "id": 1,
  "uuid": "550e8400-e29b-41d4-a716-446655440010",
  "title": "iPhone 15 Pro Max",
  "description": "iPhone 15 Pro Max 256GB Azul Titânio",
  "prize_value": 8999.00,
  "operation_cost": 899.00,
  "unit_ticket_value": 0.01,
  "min_tickets_required": 200,
  "liquidity_ratio": 85.0,
  "liquid_value": 7649.15,
  "draw_date": "2025-12-31T20:00:00.000000Z",
  "status": "active",
  "notes": "Sorteio ao vivo no Instagram",
  "created_at": "2025-01-01T00:00:00.000000Z",
  "updated_at": "2025-01-01T00:00:00.000000Z",
  "deleted_at": null
}
```

### Ticket
```json
{
  "id": 1,
  "uuid": "550e8400-e29b-41d4-a716-446655440011",
  "number": "0000001",
  "status": "available",
  "created_at": "2025-01-01T00:00:00.000000Z",
  "updated_at": "2025-01-01T00:00:00.000000Z",
  "deleted_at": null
}
```

### Wallet
```json
{
  "id": 1,
  "uuid": "550e8400-e29b-41d4-a716-446655440012",
  "user_id": 1,
  "balance": 149.50,
  "created_at": "2025-01-01T00:00:00.000000Z",
  "updated_at": "2025-01-20T10:00:00.000000Z"
}
```

### FinancialStatement
```json
{
  "id": 1,
  "uuid": "550e8400-e29b-41d4-a716-446655440013",
  "wallet_id": 1,
  "order_id": 5,
  "raffle_id": 1,
  "type": "debit",
  "amount": 2.00,
  "description": "Débito referente à aplicação em rifa - iPhone 15 Pro Max (200 tickets x R$ 0,01)",
  "created_at": "2025-01-20T10:00:00.000000Z",
  "updated_at": "2025-01-20T10:00:00.000000Z"
}
```

### RaffleTicket
```json
{
  "id": 1,
  "uuid": "abc123-def456-789",
  "raffle_id": 1,
  "user_id": 1,
  "ticket_id": 123,
  "status": "confirmed",
  "created_at": "2025-01-20T10:00:00.000000Z",
  "updated_at": "2025-01-20T10:00:00.000000Z",
  "deleted_at": null,
  "ticket": {
    "id": 123,
    "uuid": "550e8400-e29b-41d4-a716-446655440014",
    "number": "0000001",
    "status": "used"
  }
}
```

---

## Regras de Negócio

### Usuários
- Usuários podem ter apenas um patrocinador (sponsor_id)
- Username e email devem ser únicos
- Role pode ser 'user' ou 'admin'
- Status pode ser 'active', 'inactive' ou 'suspended'

### Planos
- Planos têm 3 níveis de comissão configuráveis
- plan_type pode ser 'public' ou 'private'
- Status pode ser 'active', 'inactive' ou 'archived'
- Apenas planos ativos são visíveis para customers

### Carrinho
- Usuário pode ter apenas 1 item não pago no carrinho
- Status pode ser 'pending', 'abandoned' ou 'completed'
- Ao fazer checkout, carrinho vira ordem

### Rifas e Sistema de Wallet
- **Wallet**: Cada usuário possui uma wallet com saldo em reais
- **Status de Rifas**: pending|active|completed|cancelled|inactive
- Apenas rifas com status 'active' são visíveis para aplicação
- **Aplicação em Rifas**:
  - Usuário pode aplicar apenas UMA vez por rifa
  - Pagamento feito via débito do saldo da wallet
  - Quantidade mínima: `min_tickets_required`
  - Tickets são sorteados aleatoriamente do pool disponível
  - Operação é transacional (rollback em caso de erro)
- **Financial Statements**:
  - Toda aplicação gera registro de débito na wallet
  - Descrição: "Débito referente à aplicação em rifa - {título} ({qtd} tickets x R$ {valor})"
  - Type: credit (entrada) ou debit (saída)
- **Tickets**:
  - Pool de tickets numerados sequencialmente (0000001, 0000002, ...)
  - Status: available (livre) | used (usado em rifa) | reserved (reservado)
  - Tickets são alocados aleatoriamente quando usuário aplica em rifa
- **Raffle Tickets**:
  - Representa a participação do usuário em uma rifa
  - Status: confirmed (confirmado) | winner (vencedor)
  - Vincula user + raffle + ticket específico
- **Processamento Assíncrono**:
  - Job: UserApplyToRaffleJob
  - Fila: 'raffle-applications'
  - Retry: 3 tentativas, backoff 5s, timeout 120s
  - Validações de negócio NÃO causam retry (já aplicou, saldo insuficiente, rifa inativa)

### Autenticação
- Tokens são gerenciados pelo Laravel Sanctum
- Middleware 'admin' verifica se user.role === 'admin'
- Tokens podem ser renovados sem fazer novo login

---

## Observações para Implementação Frontend

### Headers Necessários
```javascript
// Para todas as requisições autenticadas
headers: {
  'Authorization': `Bearer ${token}`,
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}
```

### Sistema de Sponsor/Referência
```javascript
// Implementar em TODAS as páginas da aplicação
function initReferralSystem() {
  // Capturar sponsor da URL se presente
  const urlParams = new URLSearchParams(window.location.search);
  const sponsor = urlParams.get('sponsor');
  
  if (sponsor && sponsor.trim() !== '') {
    localStorage.setItem('referral_sponsor', sponsor);
    // Opcional: limpar URL
    const cleanUrl = window.location.pathname + window.location.hash;
    window.history.replaceState({}, document.title, cleanUrl);
  }
}

// Executar na inicialização de cada página/componente
initReferralSystem();

// No registro, sempre incluir sponsor se existir
const savedSponsor = localStorage.getItem('referral_sponsor');
if (savedSponsor) {
  registerData.sponsor = savedSponsor;
}
```

### Tratamento de Erros
```javascript
// Verificar se token expirou
if (response.status === 401) {
  // Redirecionar para login ou tentar refresh
}

// Tratar erros de validação
if (response.status === 422) {
  // response.data.errors contém os erros de validação
}
```

### Paginação
A maioria dos endpoints de listagem retorna dados paginados no formato:
```json
{
  "data": {
    "items": [...],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 100,
      "last_page": 7
    }
  }
}
```

### UUIDs
Todos os recursos principais usam UUID como identificador público. Use sempre UUID nas URLs, nunca IDs numéricos.

---

## Changelog

### v3.0.0 - 2025-01-22
**Sistema de Wallet e Aplicação em Rifas** ✅
- ♻️ **Arquitetura Modernizada**: Migração de WalletTicket para sistema baseado em Wallet com saldo
- 💰 **Wallet System**: Carteira digital com saldo em reais para cada usuário
- 📊 **Financial Statements**: Registro completo de créditos e débitos na wallet
- 🎰 **Nova Aplicação em Rifas**:
  - Pagamento via saldo da wallet (débito automático)
  - Uma aplicação por usuário por rifa
  - Tickets sorteados aleatoriamente do pool
  - Quantidade mínima configurável (`min_tickets_required`)
  - Processamento assíncrono via Job Queue
- 🎫 **Pool de Tickets**: Sistema de numeração sequencial (0000001, 0000002, ...)
- 🔄 **Status Simplificados**: 
  - Raffle: pending|active|completed|cancelled|inactive
  - RaffleTicket: confirmed|winner
  - Ticket: available|used|reserved
- 🚀 **UserApplyToRaffleJob**: Job assíncrono para aplicações em rifas
  - Fila dedicada: 'raffle-applications'
  - Retry inteligente: 3 tentativas para erros transientes
  - Skip retry: Validações de negócio (saldo insuficiente, já aplicou, rifa inativa)
- 🗑️ **Removidos**: 
  - Campos obsoletos: `total_tickets`, `max_tickets_per_user`, `min_ticket_level`, `prize_description`
  - Model WalletTicket (substituído por Wallet + FinancialStatement)
- ✅ **242 testes passando** com cobertura completa

### v2.0.0 - 2025-10-20
**Sistema de Raffle Tickets Completo** ✅
- ✨ Novos endpoints Customer - Raffles & Tickets (5 endpoints)
  - GET `/customer/raffles` - Listar rifas disponíveis
  - GET `/customer/raffles/{uuid}` - Detalhes da rifa
  - POST `/customer/raffles/{uuid}/tickets` - Aplicar tickets
  - GET `/customer/raffles/{uuid}/my-tickets` - Listar meus tickets
  - DELETE `/customer/raffles/{uuid}/tickets` - Cancelar tickets pendentes
- 🔧 Sistema de Wallet de Tickets implementado
- 🎫 Modelos: Raffle, Ticket, WalletTicket, RaffleTicket
- ✅ 59 testes (100% cobertura): 47 Unit + 12 Feature
- 📊 Sistema de níveis de tickets (1, 2, 3...)
- 🔄 Operações transacionais com rollback automático
- 🛡️ Validações completas de regras de negócio
- 📝 Collection Postman v7 completa

### v1.0.0 - 2025-01-01
**Versão Inicial**
- 🔐 Autenticação com Sanctum
- 👤 CRUD completo para usuários
- 📦 CRUD completo para planos
- 🎰 CRUD completo para rifas (admin)
- 🛒 Sistema de carrinho e checkout
- 👨‍💼 Painel administrativo
- 💰 Sistema de comissões multinível
- 👥 Sistema de patrocínio/referência