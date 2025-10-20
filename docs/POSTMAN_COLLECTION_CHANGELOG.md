# Postman Collection Changelog

## Version 8 - 2025-10-20

### 🎉 Major Updates - Raffle Tickets System

#### Updated Endpoints

**Customer - Raffles & Tickets** (Previously separated sections merged)

1. **GET /api/v1/customer/raffles**
   - Lista raffles ativos disponíveis
   - Retorna estrutura padronizada com `raffles.data` e paginação
   - Response: 200 OK

2. **GET /api/v1/customer/raffles/{uuid}**
   - Detalhes de um raffle específico
   - Apenas raffles com status 'active' são retornados
   - Response: 200 OK | 404 Not Found

3. **POST /api/v1/customer/raffles/{uuid}/tickets** ⚠️ UPDATED
   - **Anteriormente:** `/api/v1/customer/raffles/apply-tickets`
   - **Nova URL:** `/api/v1/customer/raffles/{uuid}/tickets`
   - Body: `{ "quantity": 5 }`
   - Validações:
     - quantity é required (não mais optional)
     - Valida disponibilidade de tickets no wallet
     - Valida nível mínimo de ticket
     - Valida limite máximo por usuário
     - Valida status do raffle (deve ser 'active')
   - Response: 201 Created | 400 Bad Request | 404 Not Found
   - Response Structure:
     ```json
     {
       "message": "Tickets aplicados com sucesso",
       "applied_tickets": [...],
       "remaining_tickets": 25
     }
     ```

4. **GET /api/v1/customer/raffles/{uuid}/my-tickets**
   - Lista tickets do usuário em um raffle específico
   - Response: 200 OK | 404 Not Found
   - Response Structure:
     ```json
     {
       "tickets": [...],
       "total": 8,
       "by_status": {
         "pending": 3,
         "confirmed": 5,
         "winner": 0
       }
     }
     ```

5. **DELETE /api/v1/customer/raffles/{uuid}/tickets** ⚠️ UPDATED
   - **Anteriormente:** `/api/v1/customer/raffles/{uuid}/cancel-tickets`
   - **Nova URL:** `/api/v1/customer/raffles/{uuid}/tickets`
   - Body: `{ "raffle_ticket_uuids": ["abc123", "def456"] }`
   - **Importante:** Usa UUIDs dos raffle_tickets, não IDs
   - Apenas tickets 'pending' podem ser cancelados
   - Tickets cancelados são devolvidos ao wallet
   - Response: 200 OK | 400 Bad Request | 404 Not Found
   - Response Structure:
     ```json
     {
       "message": "Tickets cancelados com sucesso",
       "canceled_count": 2,
       "returned_tickets": 28
     }
     ```

### 🗑️ Removed Sections

- **Customer - Tickets** (merged into Customer - Raffles & Tickets)
  - Endpoints de tickets standalone foram removidos
  - Toda funcionalidade de tickets agora está integrada com raffles

### ✨ New Features

- Adicionados exemplos de responses de sucesso e erro para todos os endpoints
- Documentação detalhada de validações e regras de negócio
- Status codes padronizados (201, 200, 400, 404, 422)
- Estruturas de resposta consistentes

### 🔧 Technical Changes

1. **URL Pattern Changes:**
   - De: `/apply-tickets` → Para: `/{uuid}/tickets` (POST)
   - De: `/cancel-tickets` → Para: `/{uuid}/tickets` (DELETE)
   - Pattern RESTful: mesmo endpoint, métodos HTTP diferentes

2. **Request Body Changes:**
   - `tickets_quantity` → `quantity`
   - `ticket_ids` → `raffle_ticket_uuids`
   - IDs numéricos → UUIDs (strings)

3. **Response Structure Changes:**
   - Removido wrapper `success` e `data` de algumas responses
   - Adicionado `remaining_tickets` no apply
   - Adicionado `by_status` breakdown no my-tickets
   - Adicionado `returned_tickets` no cancelamento

### 📋 Migration Guide

Para usuários da v7 migrando para v8:

1. **Atualizar URL do Apply Tickets:**
   ```
   Antes: POST /api/v1/customer/raffles/apply-tickets
   Agora: POST /api/v1/customer/raffles/{uuid}/tickets
   ```

2. **Atualizar Body do Apply:**
   ```json
   Antes: { "raffle_uuid": "xxx", "tickets_quantity": 5 }
   Agora: { "quantity": 5 }
   ```

3. **Atualizar URL do Cancel:**
   ```
   Antes: DELETE /api/v1/customer/raffles/{uuid}/cancel-tickets
   Agora: DELETE /api/v1/customer/raffles/{uuid}/tickets
   ```

4. **Atualizar Body do Cancel:**
   ```json
   Antes: { "ticket_ids": [1, 2, 3] }
   Agora: { "raffle_ticket_uuids": ["uuid1", "uuid2"] }
   ```

5. **Atualizar Parsing de Responses:**
   - Apply: adicionar leitura de `remaining_tickets`
   - My Tickets: adicionar leitura de `by_status`
   - Cancel: adicionar leitura de `returned_tickets`

### 🧪 Testing Coverage

Todos os endpoints foram testados com 100% de cobertura:
- 59 testes automatizados (PHPUnit)
- 300 assertions
- Cobertura: Unit + Feature tests
- Status: ✅ All Passing

### 📝 Documentation

Documentação adicional disponível em:
- `docs/TESTS_RAFFLE_TICKETS.md` - Descrição detalhada dos testes
- `docs/FINAL_CORRECTIONS_SUMMARY.md` - Resumo de todas as correções
- `README.md` - Guia de uso da API

---

## Version 7 - Previous Version

[Previous changelog entries...]

---

## How to Import

1. Abra o Postman
2. Click em **Import**
3. Selecione o arquivo `API_Premia_Plus_Postman_Collection_v6_COMPLETE.json`
4. Configure as variáveis de ambiente:
   - `base_url`: URL da API (ex: http://localhost:8000)
   - `access_token`: Token JWT após login
   - `admin_token`: Token JWT de admin
   - `user_uuid`: UUID de usuário para testes
   - `plan_uuid`: UUID de plano para testes
   - `raffle_uuid`: UUID de raffle para testes

## Support

Para questões ou problemas com a API, consulte a documentação completa ou entre em contato com a equipe de desenvolvimento.
