# API Premia Plus - Postman Collection v7

## 📋 Sobre

Collection completa e validada do Postman para a API Premia Plus com todos os endpoints testados e funcionais.

**Arquivo:** `API_Premia_Plus_Postman_Collection_v7_COMPLETE.json`  
**Versão:** 7.0  
**Data:** 20/10/2025  
**Status:** ✅ 100% Funcional (59 testes passando)

---

## 🎯 O que está incluído

### 1. 🔐 Authentication (3 endpoints)
- Register User
- Login (com auto-save do token)
- Logout

### 2. 👤 Customer - Profile (3 endpoints)
- Get My Profile
- Update Profile
- Change Password

### 3. 👥 Customer - Network (3 endpoints)
- Get My Network
- Get My Sponsor
- Get My Statistics

### 4. 📦 Customer - Plans (4 endpoints)
- List All Plans
- Search Plans
- Get Promotional Plans
- Get Plan Details

### 5. 🛒 Customer - Cart (5 endpoints)
- Add Plan to Cart
- View Cart
- Remove from Cart
- Clear Cart
- Checkout

### 6. 🎫 Customer - Raffles & Tickets (5 endpoints)
- List Available Raffles
- Get Raffle Details
- **Apply Tickets to Raffle** ⭐
- **Get My Tickets in Raffle** ⭐
- **Cancel Pending Tickets** ⭐

### 7. 👨‍💼 Administrator - Users (4 endpoints)
- List All Users
- Get User Details
- Update User
- Delete User

### 8. 📦 Administrator - Plans (4 endpoints)
- List All Plans
- Create Plan
- Update Plan
- Delete Plan

### 9. 🎰 Administrator - Raffles (6 endpoints)
- List All Raffles
- Create Raffle
- Get Raffle Details
- Update Raffle
- Delete Raffle
- List Raffle Participants

### 10. 🎫 Administrator - Tickets (3 endpoints)
- Generate Tickets Batch
- List All Tickets
- Get Ticket Statistics

### 11. 📊 Administrator - Orders (3 endpoints)
- List All Orders
- Get Order Details
- Update Order Status

### 12. 🔧 Shared - Health & Monitoring (2 endpoints)
- Health Check
- API Version

---

## 🚀 Como usar

### 1. Importar no Postman

1. Abra o Postman
2. Click em **Import**
3. Selecione o arquivo `API_Premia_Plus_Postman_Collection_v7_COMPLETE.json`
4. Click em **Import**

### 2. Configurar Variáveis

A collection já vem com variáveis pré-configuradas:

- `base_url`: http://localhost:8000 (altere se necessário)
- `token`: (preenchido automaticamente no login)
- `admin_token`: (preencha manualmente após login de admin)

### 3. Autenticar

1. Execute a requisição **Login** na pasta **Authentication**
2. O token será salvo automaticamente na variável `{{token}}`
3. Todas as requisições de Customer usarão este token

### 4. Para Admin

1. Faça login com credenciais de admin
2. Copie o token retornado
3. Cole na variável `admin_token`
4. As requisições de Administrator usarão este token

---

## 🎫 Endpoints Principais - Raffle Tickets

### Aplicar Tickets em uma Rifa

```http
POST /api/v1/customer/raffles/:uuid/tickets
Authorization: Bearer {{token}}
Content-Type: application/json

{
    "quantity": 5
}
```

**Response 201:**
```json
{
    "message": "Tickets aplicados com sucesso",
    "applied_tickets": [
        {
            "uuid": "abc123...",
            "ticket_number": "00001",
            "status": "pending"
        }
    ],
    "remaining_tickets": 45
}
```

---

### Listar Meus Tickets na Rifa

```http
GET /api/v1/customer/raffles/:uuid/my-tickets
Authorization: Bearer {{token}}
```

**Response 200:**
```json
{
    "tickets": [
        {
            "uuid": "abc123...",
            "ticket_number": "00001",
            "status": "pending",
            "created_at": "2025-10-20T10:00:00Z"
        }
    ],
    "total": 5,
    "by_status": {
        "pending": 3,
        "confirmed": 2,
        "winner": 0
    }
}
```

---

### Cancelar Tickets Pendentes

```http
DELETE /api/v1/customer/raffles/:uuid/tickets
Authorization: Bearer {{token}}
Content-Type: application/json

{
    "raffle_ticket_uuids": [
        "abc123...",
        "def456..."
    ]
}
```

**Response 200:**
```json
{
    "message": "Tickets cancelados com sucesso",
    "canceled_count": 2,
    "returned_tickets": 52
}
```

---

## ✅ Validações Implementadas

### Apply Tickets
- ✅ Quantity é obrigatório
- ✅ Usuário deve ter tickets suficientes no wallet
- ✅ Rifa deve estar ativa
- ✅ Respeita max_tickets_per_user
- ✅ Verifica nível mínimo do ticket

### Cancel Tickets
- ✅ Apenas tickets pendentes podem ser cancelados
- ✅ Apenas o dono pode cancelar seus tickets
- ✅ Tickets retornam ao wallet do usuário
- ✅ Usa UUIDs para identificação

---

## 🧪 Testes

Sistema completamente testado:

- ✅ 15 testes - TicketModel
- ✅ 21 testes - RaffleTicketModel
- ✅ 11 testes - RaffleTicketService
- ✅ 12 testes - CustomerRaffleTicketController

**Total: 59 testes, 300 assertions, 100% passando**

---

## 📝 Notas Importantes

### Mudanças da v6 para v7

1. **Endpoints de Tickets corrigidos:**
   - ❌ `/raffles/:uuid/apply-tickets` → ✅ `/raffles/:uuid/tickets` (POST)
   - ❌ `/raffles/:uuid/cancel-tickets` → ✅ `/raffles/:uuid/tickets` (DELETE)

2. **Validação corrigida:**
   - Campo `quantity` agora é obrigatório
   - Campo `raffle_ticket_uuids` agora usa array de strings (UUIDs)

3. **Response padronizado:**
   - Removidos campos `success` desnecessários
   - Estruturas simplificadas e consistentes
   - Status codes corretos (201, 200, 400, 404, 422)

4. **Novo sistema de tickets:**
   - Tickets são aplicados do wallet do usuário
   - Cancelamento retorna tickets ao wallet
   - Status: pending, confirmed, winner

---

## 🐛 Troubleshooting

### Token expirado
Se receber erro 401, faça login novamente para renovar o token.

### Variáveis não funcionam
Certifique-se de que está usando a collection correta e não um fork antigo.

### Endpoints retornam 404
Verifique se o servidor está rodando e se a `base_url` está correta.

### Validação falha
Consulte os exemplos de body neste README ou na própria collection.

---

## 📚 Documentação Adicional

- **Testes:** `docs/TESTS_RAFFLE_TICKETS.md`
- **Correções:** `docs/FINAL_CORRECTIONS_SUMMARY.md`
- **Changelog:** `docs/POSTMAN_COLLECTION_CHANGELOG.md`

---

## 🤝 Contribuindo

Para adicionar novos endpoints:

1. Adicione na collection JSON
2. Teste manualmente
3. Crie testes automatizados
4. Atualize este README
5. Incremente a versão

---

**Última atualização:** 20/10/2025  
**Autor:** Neutrino Soluções em Tecnologia  
**Status:** ✅ Production Ready
