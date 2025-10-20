# API de Rifas e Tickets - Documentação Complementar

## Endpoints de Rifas (Customer)

### 1. Listar Rifas Disponíveis
**GET** `/customer/raffles`

**Acesso:** Cliente autenticado

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Opcionais):**
- `page` (int): Página atual

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Rifas listadas com sucesso",
  "data": {
    "raffles": [
      {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440010",
        "title": "Rifa iPhone 15",
        "description": "iPhone 15 Pro Max 256GB",
        "prize_value": 8000.00,
        "unit_ticket_value": 10.00,
        "tickets_required": 1,
        "min_ticket_level": 1,
        "max_tickets_per_user": 100,
        "draw_date": "2025-02-15 20:00:00",
        "status": "active"
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
  "success": true,
  "message": "Rifa encontrada com sucesso",
  "data": {
    "raffle": {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440010",
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
      "status": "active",
      "created_at": "2025-01-01T00:00:00.000000Z"
    }
  }
}
```

---

### 3. Aplicar Tickets em uma Rifa
**POST** `/customer/raffles/{uuid}/apply-tickets`

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

**Campos Opcionais:**
- `quantity` (integer, min:1): Quantidade de tickets a aplicar (padrão: tickets_required da rifa)

**Regras de Negócio:**
- Usuário deve ter tickets suficientes na carteira
- Tickets devem atender o nível mínimo da rifa (`min_ticket_level`)
- Quantidade não pode exceder o limite por usuário (`max_tickets_per_user`)
- Quantidade mínima é definida por `tickets_required`
- Sistema seleciona tickets aleatórios do pool
- Operação é transacional (tudo ou nada)

**Resposta de Sucesso (201):**
```json
{
  "success": true,
  "message": "Tickets aplicados com sucesso na rifa",
  "raffle": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440010",
    "title": "Rifa iPhone 15"
  },
  "applied_tickets": [
    {
      "ticket_number": "0042531",
      "ticket_id": 42531,
      "raffle_ticket_id": 1
    },
    {
      "ticket_number": "1823456",
      "ticket_id": 1823456,
      "raffle_ticket_id": 2
    }
  ],
  "total_applied": 5,
  "wallet_updates": [
    {
      "wallet_id": 1,
      "ticket_level": 1,
      "decremented": 5
    }
  ]
}
```

**Possíveis Erros:**

**422 - Tickets Insuficientes:**
```json
{
  "success": false,
  "message": "Tickets insuficientes. Você possui: 3, Necessário: 5"
}
```

**422 - Nível Insuficiente:**
```json
{
  "success": false,
  "message": "Você não possui tickets com nível mínimo 2"
}
```

**422 - Limite Excedido:**
```json
{
  "success": false,
  "message": "Você só pode aplicar mais 10 tickets nesta rifa (limite: 100)"
}
```

**422 - Quantidade Mínima:**
```json
{
  "success": false,
  "message": "Quantidade mínima de tickets para esta rifa: 5"
}
```

---

### 4. Meus Tickets em uma Rifa
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
  "success": true,
  "message": "Tickets listados com sucesso",
  "data": {
    "raffle": {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440010",
      "title": "Rifa iPhone 15"
    },
    "tickets": [
      {
        "raffle_ticket_id": 1,
        "ticket_id": 42531,
        "ticket_number": "0042531",
        "status": "confirmed",
        "created_at": "2025-01-01T10:00:00.000000Z"
      },
      {
        "raffle_ticket_id": 2,
        "ticket_id": 1823456,
        "ticket_number": "1823456",
        "status": "confirmed",
        "created_at": "2025-01-01T10:00:00.000000Z"
      }
    ],
    "total_tickets": 5,
    "by_status": {
      "pending": 0,
      "confirmed": 5,
      "winner": 0,
      "loser": 0
    }
  }
}
```

---

### 5. Cancelar Tickets de uma Rifa
**DELETE** `/customer/raffles/{uuid}/cancel-tickets`

**Acesso:** Cliente autenticado

**Parâmetros:**
- `uuid` (string): UUID da rifa

**Headers:**
```
Authorization: Bearer {token}
```

**Payload (Opcional):**
```json
{
  "ticket_ids": [1, 2, 3]
}
```

**Campos Opcionais:**
- `ticket_ids` (array): IDs dos raffle_tickets a cancelar (se não informado, cancela todos)

**Resposta de Sucesso (200):**
```json
{
  "success": true,
  "message": "Tickets cancelados com sucesso",
  "cancelled_tickets": [
    {
      "raffle_ticket_id": 1,
      "ticket_id": 42531,
      "ticket_number": "0042531"
    },
    {
      "raffle_ticket_id": 2,
      "ticket_id": 1823456,
      "ticket_number": "1823456"
    }
  ],
  "total_cancelled": 2
}
```

---

## Modelo de Dados

### RaffleTicket
```json
{
  "id": 1,
  "user_id": 9,
  "raffle_id": 1,
  "ticket_id": 42531,
  "status": "confirmed",
  "created_at": "2025-01-01T10:00:00.000000Z",
  "updated_at": "2025-01-01T10:00:00.000000Z",
  "deleted_at": null
}
```

### Status de RaffleTicket
- `pending` - Aplicação pendente de confirmação
- `confirmed` - Ticket confirmado na rifa
- `winner` - Ticket vencedor
- `loser` - Ticket não vencedor
- `rejected` - Aplicação rejeitada
- `cancelled` - Aplicação cancelada

---

## Regras de Negócio - Rifas

### Aplicação de Tickets
1. **Validação de Nível**: Tickets devem ter nível >= `min_ticket_level` da rifa
2. **Quantidade Mínima**: Deve aplicar pelo menos `tickets_required` tickets
3. **Limite por Usuário**: Máximo de `max_tickets_per_user` tickets (se configurado)
4. **Disponibilidade**: Tickets devem estar disponíveis na carteira do usuário
5. **Pool Global**: Tickets são selecionados aleatoriamente do pool de 10 milhões
6. **Unicidade**: Cada número só pode ser aplicado uma vez por rifa
7. **Transacional**: Operação completa ou rollback total

### Cancelamento de Tickets
1. **Status Permitidos**: Apenas tickets `pending` ou `confirmed` podem ser cancelados
2. **Propriedade**: Usuário só pode cancelar seus próprios tickets
3. **Devolução**: Tickets cancelados são devolvidos à carteira (TODO: implementar)
4. **Antes do Sorteio**: Cancelamento só permitido antes da data do sorteio

### Pool de Tickets
- **10.000.000** de tickets únicos pré-criados
- Números de **0000001** até **9999999**
- Tickets são **reutilizáveis** entre rifas diferentes
- Mesma rifa não pode ter números duplicados
- Pool compartilhado globalmente

---

## Fluxo Completo

### Compra de Plano → Aplicação em Rifa

```
1. Usuário compra Plano Bronze (10 tickets, nível 1)
   └─> Cria Order
   └─> Cria WalletTicket (total_tickets: 10, ticket_level: 1)

2. Usuário visualiza rifas disponíveis
   GET /customer/raffles
   └─> Lista rifas com status 'active'

3. Usuário decide participar da "Rifa iPhone 15"
   GET /customer/raffles/{uuid}
   └─> Verifica requisitos: min_ticket_level: 1, tickets_required: 5

4. Usuário aplica 5 tickets
   POST /customer/raffles/{uuid}/apply-tickets
   Body: { "quantity": 5 }
   
   Sistema:
   a) Valida quantidade e nível mínimo ✓
   b) Decrementa 5 tickets do WalletTicket ✓
   c) Seleciona 5 números aleatórios do pool ✓
   d) Cria 5 registros em RaffleTicket ✓
   e) Retorna números aplicados

5. Usuário consulta seus tickets na rifa
   GET /customer/raffles/{uuid}/my-tickets
   └─> Lista: 0042531, 1823456, 7654321, 0000123, 9876543

6. (Opcional) Usuário cancela alguns tickets
   DELETE /customer/raffles/{uuid}/cancel-tickets
   Body: { "ticket_ids": [1, 2] }
   └─> Cancela e devolve à carteira
```

---

## Observações Técnicas

### Performance
- Pool pré-criado evita gerações em tempo real
- Seleção aleatória otimizada com índices
- Transações garantem consistência
- Soft deletes preservam histórico

### Segurança
- Validação de propriedade (user_id)
- Verificação de status da rifa
- Limites por usuário configuráveis
- Operações transacionais

### Escalabilidade
- Pool de 10M suporta múltiplas rifas simultâneas
- Índices otimizados em raffle_tickets
- Unique constraint evita duplicações
- Paginação em listagens

---

## Próximas Implementações
- [ ] Devolução de tickets ao wallet no cancelamento
- [ ] Sistema de sorteio automático
- [ ] Notificações de resultados
- [ ] Histórico de participações
- [ ] Estatísticas de rifas por usuário
