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

## Rifas e Tickets (Customer)

> **Prefixo:** `/customer`  
> **Middleware:** `auth:sanctum`  
> **Acesso:** Cliente autenticado

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

**Resposta de Sucesso (200):**
```json
{
  "raffles": {
    "data": [
      {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440010",
        "title": "Rifa iPhone 15 Pro Max",
        "description": "iPhone 15 Pro Max 256GB Azul Titânio",
        "total_tickets": 1000,
        "tickets_required": 10,
        "max_tickets_per_user": 50,
        "min_ticket_level": 1,
        "prize_description": "iPhone 15 Pro Max 256GB",
        "prize_value": 8999.00,
        "draw_date": "2025-12-31T20:00:00.000000Z",
        "status": "active",
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
Lista todas as rifas com status "active" disponíveis para participação.

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
    "title": "Rifa iPhone 15 Pro Max",
    "description": "iPhone 15 Pro Max 256GB Azul Titânio",
    "total_tickets": 1000,
    "tickets_required": 10,
    "max_tickets_per_user": 50,
    "min_ticket_level": 1,
    "prize_description": "iPhone 15 Pro Max 256GB",
    "prize_value": 8999.00,
    "draw_date": "2025-12-31T20:00:00.000000Z",
    "status": "active",
    "created_at": "2025-01-01T00:00:00.000000Z",
    "updated_at": "2025-01-01T00:00:00.000000Z"
  }
}
```

**Resposta de Erro (404):**
```json
{
  "message": "Rifa não encontrada ou inativa"
}
```

**Descrição:**
Retorna detalhes completos de uma rifa específica. Apenas rifas com status "active" são retornadas.

---

### 3. Aplicar Tickets em uma Rifa
**POST** `/customer/raffles/{uuid}/tickets`

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
  "quantity": 5
}
```

**Campos Obrigatórios:**
- `quantity` (integer, min:1): Quantidade de tickets a aplicar

**Validações:**
- Usuário deve ter tickets suficientes no wallet
- Rifa deve estar com status "active"
- Não pode exceder `max_tickets_per_user` da rifa
- Tickets do usuário devem ter nível >= `min_ticket_level` da rifa
- Quantidade deve ser >= 1

**Resposta de Sucesso (201):**
```json
{
  "message": "Tickets aplicados com sucesso",
  "applied_tickets": [
    {
      "uuid": "abc123-def456-789",
      "ticket_number": "00001",
      "status": "pending",
      "level": 2,
      "created_at": "2025-01-20T10:00:00.000000Z"
    },
    {
      "uuid": "abc123-def456-790",
      "ticket_number": "00002",
      "status": "pending",
      "level": 2,
      "created_at": "2025-01-20T10:00:00.000000Z"
    }
  ],
  "remaining_tickets": 45
}
```

**Respostas de Erro:**

**400 - Tickets Insuficientes:**
```json
{
  "message": "Você não possui tickets suficientes."
}
```

**400 - Nível Insuficiente:**
```json
{
  "message": "Você não possui tickets do nível mínimo exigido (3)."
}
```

**400 - Limite Excedido:**
```json
{
  "message": "Quantidade excede o limite de 50 tickets por usuário para esta rifa."
}
```

**400 - Rifa Inativa:**
```json
{
  "message": "Esta rifa não está ativa."
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
Aplica tickets do wallet do usuário em uma rifa específica. Os tickets são consumidos do wallet e criados na tabela `raffle_tickets` com status "pending". A operação é transacional, garantindo atomicidade.

**Regras de Negócio:**
1. Tickets são consumidos do wallet em ordem de criação (FIFO)
2. Apenas tickets com nível adequado são utilizados
3. Status inicial dos tickets na rifa é "pending"
4. Transação é revertida em caso de erro
5. Retorna total de tickets restantes no wallet

---

### 4. Listar Meus Tickets em uma Rifa
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
  "tickets": [
    {
      "uuid": "abc123-def456-789",
      "ticket_number": "00001",
      "status": "pending",
      "level": 2,
      "created_at": "2025-01-20T10:00:00.000000Z",
      "updated_at": "2025-01-20T10:00:00.000000Z"
    },
    {
      "uuid": "abc123-def456-790",
      "ticket_number": "00002",
      "status": "confirmed",
      "level": 2,
      "created_at": "2025-01-20T10:00:00.000000Z",
      "updated_at": "2025-01-20T11:00:00.000000Z"
    },
    {
      "uuid": "abc123-def456-791",
      "ticket_number": "00003",
      "status": "winner",
      "level": 2,
      "created_at": "2025-01-20T10:00:00.000000Z",
      "updated_at": "2025-01-20T20:00:00.000000Z"
    }
  ],
  "total": 3,
  "by_status": {
    "pending": 1,
    "confirmed": 1,
    "winner": 1
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
Lista todos os tickets do usuário autenticado em uma rifa específica, incluindo breakdown por status.

**Status Possíveis:**
- `pending`: Ticket aplicado, aguardando confirmação
- `confirmed`: Ticket confirmado para o sorteio
- `winner`: Ticket vencedor do sorteio

---

### 5. Cancelar Tickets Pendentes
**DELETE** `/customer/raffles/{uuid}/tickets`

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
  "raffle_ticket_uuids": [
    "abc123-def456-789",
    "abc123-def456-790"
  ]
}
```

**Campos Obrigatórios:**
- `raffle_ticket_uuids` (array de strings): UUIDs dos tickets a cancelar

**Validações:**
- Apenas tickets com status "pending" podem ser cancelados
- Tickets devem pertencer ao usuário autenticado
- Tickets devem pertencer à rifa especificada

**Resposta de Sucesso (200):**
```json
{
  "message": "Tickets cancelados com sucesso",
  "canceled_count": 2,
  "returned_tickets": 52
}
```

**Resposta de Erro (400):**
```json
{
  "message": "Alguns tickets não puderam ser cancelados (já estão confirmados ou não pertencem a você)."
}
```

**Resposta de Erro (404):**
```json
{
  "message": "Rifa não encontrada"
}
```

**Descrição:**
Cancela tickets pendentes do usuário em uma rifa. Os tickets cancelados são devolvidos ao wallet do usuário. Apenas tickets com status "pending" podem ser cancelados. A operação é transacional.

**Regras de Negócio:**
1. Apenas tickets "pending" podem ser cancelados
2. Tickets confirmados ou vencedores não podem ser cancelados
3. Tickets são devolvidos ao wallet com os mesmos atributos
4. `returned_tickets` indica o total de tickets no wallet após o cancelamento
5. Se nenhum ticket for cancelado (todos confirmados), retorna erro 400

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
  "title": "Rifa iPhone 15",
  "description": "iPhone 15 Pro Max 256GB",
  "prize_value": 8000.00,
  "operation_cost": 800.00,
  "unit_ticket_value": 10.00,
  "liquidity_ratio": 85.0,
  "tickets_required": 1,
  "min_ticket_level": 1,
  "max_tickets_per_user": 100,
  "draw_date": "2025-02-15 20:00:00",
  "status": "scheduled"
}
```

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
  "title": "Rifa iPhone 15 Pro Max",
  "description": "iPhone 15 Pro Max 256GB Azul Titânio",
  "total_tickets": 1000,
  "tickets_required": 10,
  "max_tickets_per_user": 50,
  "min_ticket_level": 1,
  "prize_description": "iPhone 15 Pro Max 256GB",
  "prize_value": 8999.00,
  "operation_cost": 899.00,
  "unit_ticket_value": 10.00,
  "liquidity_ratio": 85.0,
  "draw_date": "2025-12-31T20:00:00.000000Z",
  "status": "active",
  "created_at": "2025-01-01T00:00:00.000000Z",
  "updated_at": "2025-01-01T00:00:00.000000Z",
  "deleted_at": null
}
```

### Ticket
```json
{
  "id": 1,
  "number": "00001",
  "level": 2,
  "status": "available",
  "created_at": "2025-01-01T00:00:00.000000Z",
  "updated_at": "2025-01-01T00:00:00.000000Z",
  "deleted_at": null
}
```

### WalletTicket
```json
{
  "id": 1,
  "uuid": "550e8400-e29b-41d4-a716-446655440011",
  "user_id": 1,
  "order_id": 5,
  "plan_id": 2,
  "total_tickets": 50,
  "level": 2,
  "status": "active",
  "created_at": "2025-01-01T00:00:00.000000Z",
  "updated_at": "2025-01-01T00:00:00.000000Z"
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
  "status": "pending",
  "created_at": "2025-01-20T10:00:00.000000Z",
  "updated_at": "2025-01-20T10:00:00.000000Z",
  "deleted_at": null,
  "ticket": {
    "id": 123,
    "number": "00001",
    "level": 2
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

### Rifas e Tickets
- Apenas rifas com status 'active' são visíveis para customers
- Status de rifas: open|closed|drawn|scheduled
- Tickets têm níveis (1, 2, 3...) que definem sua qualidade
- Usuários precisam de tickets no wallet para participar de rifas
- `max_tickets_per_user` limita participação individual por rifa
- `min_ticket_level` garante qualidade mínima dos tickets aplicados
- Tickets na rifa têm status: pending|confirmed|winner
- Apenas tickets "pending" podem ser cancelados
- Cancelamento retorna tickets ao wallet do usuário
- Operações de aplicação/cancelamento são transacionais (rollback em erro)
- Tickets são consumidos do wallet em ordem FIFO (First In, First Out)
- `tickets_required` define número mínimo de tickets para participação
- Sistema de wallet gerencia tickets virtuais por usuário/order/plan

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