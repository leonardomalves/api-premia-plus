# 📮 Postman Collection v8 - Quick Reference

## 🎯 Endpoints de Raffle Tickets (Atualizado em 2025-10-20)

### 📋 Resumo das Mudanças

| Funcionalidade | v7 (Antigo) | v8 (Novo) | Status |
|----------------|-------------|-----------|---------|
| **Aplicar Tickets** | POST `/raffles/apply-tickets` | POST `/raffles/{uuid}/tickets` | ✅ Updated |
| **Cancelar Tickets** | DELETE `/raffles/{uuid}/cancel-tickets` | DELETE `/raffles/{uuid}/tickets` | ✅ Updated |
| **Meus Tickets** | GET `/raffles/{uuid}/my-tickets` | GET `/raffles/{uuid}/my-tickets` | ✅ Unchanged |
| **Listar Raffles** | GET `/raffles` | GET `/raffles` | ✅ Unchanged |
| **Ver Raffle** | GET `/raffles/{uuid}` | GET `/raffles/{uuid}` | ✅ Unchanged |

---

## 🔌 Endpoints Detalhados

### 1️⃣ Listar Raffles Disponíveis

```http
GET /api/v1/customer/raffles
Authorization: Bearer {token}
```

**Response 200:**
```json
{
  "raffles": {
    "data": [
      {
        "uuid": "123e4567-e89b-12d3-a456-426614174000",
        "title": "iPhone 15 Pro Max",
        "prize_value": 8999.99,
        "total_tickets": 500,
        "tickets_required": 400,
        "max_tickets_per_user": 10,
        "min_ticket_level": 1,
        "status": "active",
        "draw_date": "2025-12-31"
      }
    ],
    "current_page": 1,
    "per_page": 15,
    "total": 50,
    "last_page": 4
  }
}
```

---

### 2️⃣ Ver Detalhes de um Raffle

```http
GET /api/v1/customer/raffles/{uuid}
Authorization: Bearer {token}
```

**Response 200:**
```json
{
  "raffle": {
    "uuid": "123e4567-e89b-12d3-a456-426614174000",
    "title": "iPhone 15 Pro Max",
    "description": "Sorteio de iPhone 15 Pro Max 512GB",
    "prize_value": 8999.99,
    "operation_cost": 500.00,
    "unit_ticket_value": 25.00,
    "total_tickets": 500,
    "tickets_required": 400,
    "max_tickets_per_user": 10,
    "min_ticket_level": 1,
    "status": "active",
    "draw_date": "2025-12-31",
    "created_at": "2025-10-20T10:00:00Z",
    "updated_at": "2025-10-20T10:00:00Z"
  }
}
```

---

### 3️⃣ Aplicar Tickets em um Raffle ⚠️ ATUALIZADO

```http
POST /api/v1/customer/raffles/{uuid}/tickets
Authorization: Bearer {token}
Content-Type: application/json

{
  "quantity": 5
}
```

**Validações:**
- ✅ `quantity` é obrigatório (min: 1)
- ✅ Verifica se usuário tem tickets suficientes no wallet
- ✅ Verifica nível mínimo de ticket (`min_ticket_level`)
- ✅ Verifica limite por usuário (`max_tickets_per_user`)
- ✅ Verifica se raffle está ativo (`status: 'active'`)

**Response 201 (Sucesso):**
```json
{
  "message": "Tickets aplicados com sucesso",
  "applied_tickets": [
    {
      "uuid": "abc123-def456-ghi789",
      "ticket_number": 42,
      "level": 2,
      "status": "pending"
    },
    {
      "uuid": "jkl012-mno345-pqr678",
      "ticket_number": 157,
      "level": 2,
      "status": "pending"
    }
  ],
  "remaining_tickets": 25
}
```

**Response 400 (Tickets Insuficientes):**
```json
{
  "message": "Você não possui tickets suficientes."
}
```

**Response 400 (Excede Limite):**
```json
{
  "message": "Quantidade excede o limite de 10 tickets por usuário para esta rifa."
}
```

**Response 400 (Nível Insuficiente):**
```json
{
  "message": "Você não possui tickets do nível mínimo exigido (3)."
}
```

**Response 400 (Raffle Inativo):**
```json
{
  "message": "Esta rifa não está ativa."
}
```

---

### 4️⃣ Ver Meus Tickets em um Raffle

```http
GET /api/v1/customer/raffles/{uuid}/my-tickets
Authorization: Bearer {token}
```

**Response 200:**
```json
{
  "tickets": [
    {
      "uuid": "abc123-def456-ghi789",
      "ticket_number": 42,
      "ticket_level": 2,
      "status": "pending",
      "created_at": "2025-10-20T10:30:00Z"
    },
    {
      "uuid": "jkl012-mno345-pqr678",
      "ticket_number": 157,
      "ticket_level": 2,
      "status": "confirmed",
      "created_at": "2025-10-20T11:00:00Z"
    }
  ],
  "total": 8,
  "by_status": {
    "pending": 3,
    "confirmed": 5,
    "winner": 0
  }
}
```

---

### 5️⃣ Cancelar Tickets de um Raffle ⚠️ ATUALIZADO

```http
DELETE /api/v1/customer/raffles/{uuid}/tickets
Authorization: Bearer {token}
Content-Type: application/json

{
  "raffle_ticket_uuids": [
    "abc123-def456-ghi789",
    "jkl012-mno345-pqr678"
  ]
}
```

**Regras:**
- ✅ Apenas tickets com status `pending` podem ser cancelados
- ✅ Tickets devem pertencer ao usuário autenticado
- ✅ Tickets cancelados são devolvidos ao wallet
- ✅ Usa UUIDs dos raffle_tickets (não IDs numéricos)

**Response 200 (Sucesso):**
```json
{
  "message": "Tickets cancelados com sucesso",
  "canceled_count": 2,
  "returned_tickets": 28
}
```

**Response 400 (Tickets Confirmados):**
```json
{
  "message": "Alguns tickets não puderam ser cancelados (já estão confirmados ou não pertencem a você)."
}
```

---

## 🔄 Guia de Migração v7 → v8

### Atualizar Apply Tickets

**Antes (v7):**
```javascript
// URL
POST /api/v1/customer/raffles/apply-tickets

// Body
{
  "raffle_uuid": "123e4567-e89b-12d3-a456-426614174000",
  "tickets_quantity": 5
}
```

**Agora (v8):**
```javascript
// URL (raffle_uuid vai na URL)
POST /api/v1/customer/raffles/123e4567-e89b-12d3-a456-426614174000/tickets

// Body (simplificado)
{
  "quantity": 5
}

// Response (adicionado remaining_tickets)
{
  "message": "Tickets aplicados com sucesso",
  "applied_tickets": [...],
  "remaining_tickets": 25  // ← NOVO
}
```

---

### Atualizar Cancel Tickets

**Antes (v7):**
```javascript
// URL
DELETE /api/v1/customer/raffles/{uuid}/cancel-tickets

// Body (IDs numéricos)
{
  "ticket_ids": [1, 2, 3]
}
```

**Agora (v8):**
```javascript
// URL (sem /cancel-tickets)
DELETE /api/v1/customer/raffles/{uuid}/tickets

// Body (UUIDs em vez de IDs)
{
  "raffle_ticket_uuids": [
    "abc123-def456-ghi789",
    "jkl012-mno345-pqr678"
  ]
}

// Response (adicionado returned_tickets)
{
  "message": "Tickets cancelados com sucesso",
  "canceled_count": 2,
  "returned_tickets": 28  // ← NOVO
}
```

---

## 📊 Status Codes

| Status | Significado | Quando Ocorre |
|--------|-------------|---------------|
| **200** | OK | GET requests bem-sucedidos, DELETE bem-sucedido |
| **201** | Created | POST de aplicação de tickets bem-sucedido |
| **400** | Bad Request | Validação de negócio falhou (tickets insuficientes, limite excedido, etc) |
| **404** | Not Found | Raffle não encontrado ou inativo |
| **422** | Unprocessable | Validação de formulário falhou (campos obrigatórios ausentes) |
| **401** | Unauthorized | Token ausente ou inválido |

---

## 🎯 Variáveis do Postman

Configure estas variáveis no Postman para usar a collection:

```json
{
  "base_url": "http://localhost:8000",
  "access_token": "seu_token_jwt_aqui",
  "raffle_uuid": "123e4567-e89b-12d3-a456-426614174000"
}
```

---

## ✅ Testes Automatizados

Todos os endpoints foram testados com:
- **59 testes** (PHPUnit)
- **300 assertions**
- **100% passing**
- Cobertura: Unit + Feature tests

### Executar os testes:

```bash
# Todos os testes de raffle tickets
php artisan test --filter="CustomerRaffleTicket|RaffleTicketService|TicketModel|RaffleTicketModel"

# Apenas testes de Feature
php artisan test --filter="CustomerRaffleTicketTest"

# Apenas testes de Service
php artisan test --filter="RaffleTicketServiceTest"
```

---

## 📚 Documentação Adicional

- `POSTMAN_COLLECTION_CHANGELOG.md` - Changelog completo com detalhes de migração
- `API_Premia_Plus_Postman_Collection_v6_COMPLETE.json` - Collection atualizada
- `README.md` - Guia geral da API

---

## 🆘 Suporte

Para questões ou problemas:
1. Consulte a documentação completa
2. Verifique os exemplos de response na collection
3. Execute os testes automatizados
4. Entre em contato com a equipe de desenvolvimento

---

**Última Atualização:** 2025-10-20  
**Versão da Collection:** v8  
**Status:** ✅ Production Ready
