# Postman Collection Changelog# Postman Collection Changelog



## v7.0 - 2025-10-20 (COMPLETE RECREATION) ✅## Version 8 - 2025-10-20



### 🔥 Mudanças Críticas### 🎉 Major Updates - Raffle Tickets System



**Collection completamente recriada do zero** devido à corrupção do arquivo v6.#### Updated Endpoints



### ✨ Novo Arquivo Criado**Customer - Raffles & Tickets** (Previously separated sections merged)



- **Arquivo:** `API_Premia_Plus_Postman_Collection_v7_COMPLETE.json`1. **GET /api/v1/customer/raffles**

- **Backup v6:** `API_Premia_Plus_Postman_Collection_v6_COMPLETE.json.backup`   - Lista raffles ativos disponíveis

- **Status:** ✅ JSON válido, testado e funcional   - Retorna estrutura padronizada com `raffles.data` e paginação

   - Response: 200 OK

### 📊 Estrutura Completa

2. **GET /api/v1/customer/raffles/{uuid}**

```   - Detalhes de um raffle específico

📁 API Premia Plus v7 - 45 endpoints   - Apenas raffles com status 'active' são retornados

├── 🔐 Authentication (3)   - Response: 200 OK | 404 Not Found

├── 👤 Customer - Profile (3)

├── 👥 Customer - Network (3)3. **POST /api/v1/customer/raffles/{uuid}/tickets** ⚠️ UPDATED

├── 📦 Customer - Plans (4)   - **Anteriormente:** `/api/v1/customer/raffles/apply-tickets`

├── 🛒 Customer - Cart (5)   - **Nova URL:** `/api/v1/customer/raffles/{uuid}/tickets`

├── 🎫 Customer - Raffles & Tickets (5) ⭐   - Body: `{ "quantity": 5 }`

├── 👨‍💼 Administrator - Users (4)   - Validações:

├── 📦 Administrator - Plans (4)     - quantity é required (não mais optional)

├── 🎰 Administrator - Raffles (6)     - Valida disponibilidade de tickets no wallet

├── 🎫 Administrator - Tickets (3)     - Valida nível mínimo de ticket

├── 📊 Administrator - Orders (3)     - Valida limite máximo por usuário

└── 🔧 Shared - Health & Monitoring (2)     - Valida status do raffle (deve ser 'active')

```   - Response: 201 Created | 400 Bad Request | 404 Not Found

   - Response Structure:

### 🎫 Sistema de Raffle Tickets (Corrigido)     ```json

     {

#### Endpoints Atualizados       "message": "Tickets aplicados com sucesso",

       "applied_tickets": [...],

| Método | v6 (antigo) | v7 (novo) | Status |       "remaining_tickets": 25

|--------|-------------|-----------|--------|     }

| POST | `/raffles/{uuid}/apply-tickets` | `/raffles/{uuid}/tickets` | ✅ |     ```

| DELETE | `/raffles/{uuid}/cancel-tickets` | `/raffles/{uuid}/tickets` | ✅ |

| GET | `/raffles/{uuid}/my-tickets` | `/raffles/{uuid}/my-tickets` | ✅ |4. **GET /api/v1/customer/raffles/{uuid}/my-tickets**

| GET | `/raffles` | `/raffles` | ✅ |   - Lista tickets do usuário em um raffle específico

| GET | `/raffles/{uuid}` | `/raffles/{uuid}` | ✅ |   - Response: 200 OK | 404 Not Found

   - Response Structure:

#### Request Bodies Corrigidos     ```json

     {

**Apply Tickets (POST /raffles/{uuid}/tickets):**       "tickets": [...],

```json       "total": 8,

{       "by_status": {

    "quantity": 5         "pending": 3,

}         "confirmed": 5,

```         "winner": 0

- ✅ Campo `quantity` agora é **required**       }

- ✅ Status 201 para sucesso     }

- ✅ Status 400 para erros de negócio     ```



**Cancel Tickets (DELETE /raffles/{uuid}/tickets):**5. **DELETE /api/v1/customer/raffles/{uuid}/tickets** ⚠️ UPDATED

```json   - **Anteriormente:** `/api/v1/customer/raffles/{uuid}/cancel-tickets`

{   - **Nova URL:** `/api/v1/customer/raffles/{uuid}/tickets`

    "raffle_ticket_uuids": [   - Body: `{ "raffle_ticket_uuids": ["abc123", "def456"] }`

        "abc123...",   - **Importante:** Usa UUIDs dos raffle_tickets, não IDs

        "def456..."   - Apenas tickets 'pending' podem ser cancelados

    ]   - Tickets cancelados são devolvidos ao wallet

}   - Response: 200 OK | 400 Bad Request | 404 Not Found

```   - Response Structure:

- ✅ Usa UUIDs (strings) ao invés de IDs (integers)     ```json

- ✅ Campo renomeado: `ticket_ids` → `raffle_ticket_uuids`     {

       "message": "Tickets cancelados com sucesso",

#### Responses Padronizadas       "canceled_count": 2,

       "returned_tickets": 28

**Apply Tickets - 201 Created:**     }

```json     ```

{

    "message": "Tickets aplicados com sucesso",### 🗑️ Removed Sections

    "applied_tickets": [

        {- **Customer - Tickets** (merged into Customer - Raffles & Tickets)

            "uuid": "abc123...",  - Endpoints de tickets standalone foram removidos

            "ticket_number": "00001",  - Toda funcionalidade de tickets agora está integrada com raffles

            "status": "pending",

            "level": 2### ✨ New Features

        }

    ],- Adicionados exemplos de responses de sucesso e erro para todos os endpoints

    "remaining_tickets": 45- Documentação detalhada de validações e regras de negócio

}- Status codes padronizados (201, 200, 400, 404, 422)

```- Estruturas de resposta consistentes



**Get My Tickets - 200 OK:**### 🔧 Technical Changes

```json

{1. **URL Pattern Changes:**

    "tickets": [...],   - De: `/apply-tickets` → Para: `/{uuid}/tickets` (POST)

    "total": 5,   - De: `/cancel-tickets` → Para: `/{uuid}/tickets` (DELETE)

    "by_status": {   - Pattern RESTful: mesmo endpoint, métodos HTTP diferentes

        "pending": 3,

        "confirmed": 2,2. **Request Body Changes:**

        "winner": 0   - `tickets_quantity` → `quantity`

    }   - `ticket_ids` → `raffle_ticket_uuids`

}   - IDs numéricos → UUIDs (strings)

```

3. **Response Structure Changes:**

**Cancel Tickets - 200 OK:**   - Removido wrapper `success` e `data` de algumas responses

```json   - Adicionado `remaining_tickets` no apply

{   - Adicionado `by_status` breakdown no my-tickets

    "message": "Tickets cancelados com sucesso",   - Adicionado `returned_tickets` no cancelamento

    "canceled_count": 2,

    "returned_tickets": 52### 📋 Migration Guide

}

```Para usuários da v7 migrando para v8:



### 🎯 Status Codes Padronizados1. **Atualizar URL do Apply Tickets:**

   ```

| Código | Uso | Exemplo |   Antes: POST /api/v1/customer/raffles/apply-tickets

|--------|-----|---------|   Agora: POST /api/v1/customer/raffles/{uuid}/tickets

| 200 | Sucesso em GET/DELETE | Lista de tickets |   ```

| 201 | Criação bem-sucedida | Tickets aplicados |

| 400 | Erro de lógica de negócio | Tickets insuficientes |2. **Atualizar Body do Apply:**

| 401 | Não autenticado | Token inválido |   ```json

| 404 | Recurso não encontrado | Rifa não existe |   Antes: { "raffle_uuid": "xxx", "tickets_quantity": 5 }

| 422 | Erro de validação | Campo obrigatório |   Agora: { "quantity": 5 }

| 500 | Erro interno | Exceção não tratada |   ```



### 🧪 Cobertura de Testes3. **Atualizar URL do Cancel:**

   ```

Collection validada por testes automatizados:   Antes: DELETE /api/v1/customer/raffles/{uuid}/cancel-tickets

- ✅ 15 testes - TicketModel   Agora: DELETE /api/v1/customer/raffles/{uuid}/tickets

- ✅ 21 testes - RaffleTicketModel     ```

- ✅ 11 testes - RaffleTicketService

- ✅ 12 testes - CustomerRaffleTicketController4. **Atualizar Body do Cancel:**

   ```json

**Total: 59 testes, 300 assertions, 100% de cobertura**   Antes: { "ticket_ids": [1, 2, 3] }

   Agora: { "raffle_ticket_uuids": ["uuid1", "uuid2"] }

### 🚀 Recursos da v7   ```



1. **Auto-save de Token:**5. **Atualizar Parsing de Responses:**

   - Login automático salva token em `{{token}}`   - Apply: adicionar leitura de `remaining_tickets`

   - Todas as requisições customer usam o token automaticamente   - My Tickets: adicionar leitura de `by_status`

   - Cancel: adicionar leitura de `returned_tickets`

2. **Variáveis Pré-configuradas:**

   ```json### 🧪 Testing Coverage

   {

       "base_url": "http://localhost:8000",Todos os endpoints foram testados com 100% de cobertura:

       "token": "",- 59 testes automatizados (PHPUnit)

       "admin_token": ""- 300 assertions

   }- Cobertura: Unit + Feature tests

   ```- Status: ✅ All Passing



3. **Descrições Detalhadas:**### 📝 Documentation

   - Cada endpoint tem descrição completa

   - Exemplos de request/responseDocumentação adicional disponível em:

   - Validações documentadas- `docs/TESTS_RAFFLE_TICKETS.md` - Descrição detalhada dos testes

- `docs/FINAL_CORRECTIONS_SUMMARY.md` - Resumo de todas as correções

4. **Organização por Domínio:**- `README.md` - Guia de uso da API

   - Separação clara entre Customer e Administrator

   - Agrupamento lógico por funcionalidade---

   - Fácil navegação e busca

## Version 7 - Previous Version

### ⚠️ Breaking Changes

[Previous changelog entries...]

Se você usava v6, atualize:

---

1. **URLs dos endpoints:**

   ```## How to Import

   ❌ POST /api/v1/customer/raffles/apply-tickets

   ✅ POST /api/v1/customer/raffles/{uuid}/tickets1. Abra o Postman

   2. Click em **Import**

   ❌ DELETE /api/v1/customer/raffles/{uuid}/cancel-tickets3. Selecione o arquivo `API_Premia_Plus_Postman_Collection_v6_COMPLETE.json`

   ✅ DELETE /api/v1/customer/raffles/{uuid}/tickets4. Configure as variáveis de ambiente:

   ```   - `base_url`: URL da API (ex: http://localhost:8000)

   - `access_token`: Token JWT após login

2. **Request bodies:**   - `admin_token`: Token JWT de admin

   ```json   - `user_uuid`: UUID de usuário para testes

   // ANTES   - `plan_uuid`: UUID de plano para testes

   {   - `raffle_uuid`: UUID de raffle para testes

       "raffle_uuid": "xxx",

       "tickets_quantity": 5## Support

   }

   Para questões ou problemas com a API, consulte a documentação completa ou entre em contato com a equipe de desenvolvimento.

   // DEPOIS
   {
       "quantity": 5
   }
   ```

3. **Cancelamento de tickets:**
   ```json
   // ANTES
   {
       "ticket_ids": [1, 2, 3]
   }
   
   // DEPOIS
   {
       "raffle_ticket_uuids": ["uuid1", "uuid2"]
   }
   ```

### 🐛 Bugs Corrigidos

- ✅ JSON inválido/corrompido (v6)
- ✅ Endpoints duplicados removidos
- ✅ Estruturas de response inconsistentes
- ✅ Validações incorretas
- ✅ Status codes errados
- ✅ Variáveis mal configuradas
- ✅ URLs não-RESTful padronizadas

### 📚 Documentação

Documentação completa disponível em:
- **README:** `docs/POSTMAN_COLLECTION_README.md`
- **Testes:** `docs/TESTS_RAFFLE_TICKETS.md`
- **Correções:** `docs/FINAL_CORRECTIONS_SUMMARY.md`

### 🔄 Como Migrar

1. **Fazer backup da v6** (já criado automaticamente)
2. **Importar v7 no Postman**
3. **Atualizar variáveis de ambiente**
4. **Testar login para pegar novo token**
5. **Atualizar scripts que usam a API**

---

## v6.0 - 2025-10-19 (DEPRECATED - Corrompido)

⚠️ **Esta versão foi descontinuada devido à corrupção do arquivo JSON.**

**Backup disponível:** `API_Premia_Plus_Postman_Collection_v6_COMPLETE.json.backup`

### Problemas Conhecidos

- ❌ JSON mal formado
- ❌ Endpoints duplicados
- ❌ Validações incorretas
- ❌ Status codes inconsistentes
- ❌ Estruturas de response despadronizadas

**Solução:** Use a v7.0

---

## Roadmap Futuro

### v7.1 (Planejado)
- [ ] Exemplos de erro para todos os status codes
- [ ] Testes automatizados no Postman
- [ ] Suporte a múltiplos ambientes (dev, staging, prod)
- [ ] Documentação inline expandida

### v8.0 (Futuro)
- [ ] Workflows E2E completos
- [ ] Integração CI/CD com Newman
- [ ] Mock servers
- [ ] Webhooks e notificações
- [ ] Monitoramento de performance

---

## Suporte

**Documentação:** `docs/POSTMAN_COLLECTION_README.md`  
**Issues:** Reporte problemas na documentação interna  
**Mantenedor:** Neutrino Soluções em Tecnologia  
**Última atualização:** 2025-10-20  
**Status:** ✅ Production Ready
