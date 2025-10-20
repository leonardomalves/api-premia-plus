# Refatoração do Sistema de Tickets e Rifas - Resumo

## 🎯 Objetivo
Corrigir a implementação do sistema de aplicação de tickets em rifas, usando corretamente a tabela intermediária `raffle_tickets`.

---

## ✅ Correções Implementadas

### 1. **Modelo RaffleTicket.php**
- ✅ Adicionado `$fillable` com todos os campos necessários
- ✅ Adicionado constantes de status
- ✅ Adicionado `$casts` para datas
- ✅ Criados métodos de verificação de status (`isPending()`, `isConfirmed()`, etc.)
- ✅ Criados métodos para alterar status (`markAsConfirmed()`, `markAsWinner()`, etc.)
- ✅ Criados scopes úteis (`scopePending()`, `scopeByUser()`, etc.)
- ✅ Tipagem adequada nos relacionamentos (BelongsTo)

### 2. **Modelo Raffle.php**
- ✅ Adicionado relacionamento `raffleTickets()` (hasMany)
- ✅ Adicionado relacionamento `participants()` (belongsToMany via raffle_tickets)

### 3. **Modelo User.php**
- ✅ Adicionado relacionamento `raffleTickets()` (hasMany)
- ✅ Adicionado relacionamento `participatedRaffles()` (belongsToMany via raffle_tickets)

### 4. **Modelo Ticket.php**
- ✅ Adicionado relacionamento `raffleTickets()` (hasMany)
- ✅ Adicionado relacionamento `raffles()` (belongsToMany via raffle_tickets)

### 5. **UserApplyToRaffleSeed.php**
- ✅ Importado modelo `RaffleTicket`
- ✅ Importado `DB` facade para transações
- ✅ Adicionada validação de existência de usuário e rifas
- ✅ **CORREÇÃO CRÍTICA**: Removida tentativa de atualizar campos inexistentes em `tickets`
- ✅ **CORREÇÃO CRÍTICA**: Implementado uso correto de `raffle_tickets`
- ✅ Adicionada busca de tickets usando `whereDoesntHave()` para evitar duplicação
- ✅ Implementada transação completa (`DB::beginTransaction()`)
- ✅ Adicionado rollback automático em caso de erro
- ✅ Criação de registros em `RaffleTicket` com status correto
- ✅ Validações aprimoradas com exceções

### 6. **Service: RaffleTicketService.php** (NOVO)
- ✅ Serviço completo para gerenciar aplicação de tickets
- ✅ Método `applyTicketsToRaffle()` com validações completas:
  - Valida quantidade mínima
  - Valida limite máximo por usuário
  - Valida nível mínimo de tickets
  - Valida disponibilidade na carteira
  - Transação completa
  - Seleção aleatória do pool
  - Criação em raffle_tickets
- ✅ Método `cancelTicketsFromRaffle()` para cancelamento
- ✅ Método `getUserTicketsInRaffle()` para listagem
- ✅ Retorno padronizado com arrays estruturados

### 7. **Controller: CustomerRaffleTicketController.php** (NOVO)
- ✅ Endpoint para listar rifas disponíveis
- ✅ Endpoint para detalhes de uma rifa
- ✅ Endpoint para aplicar tickets em rifa
- ✅ Endpoint para listar meus tickets em uma rifa
- ✅ Endpoint para cancelar tickets de uma rifa
- ✅ Tratamento completo de erros
- ✅ Validações de request
- ✅ Responses padronizados

### 8. **Rotas: customer.php**
- ✅ `GET /customer/raffles` - Listar rifas
- ✅ `GET /customer/raffles/{uuid}` - Detalhes da rifa
- ✅ `POST /customer/raffles/{uuid}/apply-tickets` - Aplicar tickets
- ✅ `GET /customer/raffles/{uuid}/my-tickets` - Meus tickets
- ✅ `DELETE /customer/raffles/{uuid}/cancel-tickets` - Cancelar tickets

### 9. **Documentação: API_RAFFLES_TICKETS.md** (NOVO)
- ✅ Documentação completa de todos os endpoints
- ✅ Exemplos de payloads e responses
- ✅ Regras de negócio detalhadas
- ✅ Modelo de dados
- ✅ Fluxo completo do sistema
- ✅ Observações técnicas

---

## 🔄 Arquitetura Corrigida

### **ANTES (INCORRETO):**
```
tickets (tabela)
├─ id
├─ number
├─ user_id ❌ (não existe)
├─ raffle_id ❌ (não existe)
├─ ticket_level ❌ (não existe)
└─ status ❌ (não existe)
```

### **DEPOIS (CORRETO):**
```
tickets (pool de números)
├─ id
├─ number (único)
└─ timestamps

raffle_tickets (aplicações)
├─ id
├─ user_id → users.id
├─ raffle_id → raffles.id
├─ ticket_id → tickets.id
├─ status (pending/confirmed/winner/loser)
└─ timestamps

UNIQUE (raffle_id, ticket_id) ← Garante unicidade
```

---

## 🎯 Fluxo Correto Implementado

```
1. Pool de Tickets (10M pré-criados)
   └─> tickets table: apenas números únicos

2. Usuário Compra Plano
   └─> Cria wallet_tickets com quantidade e nível

3. Usuário Aplica em Rifa
   └─> Decrementa wallet_tickets
   └─> Seleciona tickets aleatórios do pool
   └─> Cria registros em raffle_tickets
   └─> Vincula: user + raffle + ticket

4. Sistema de Sorteio (futuro)
   └─> Atualiza status em raffle_tickets
   └─> Marca winner/loser
```

---

## 🧪 Como Testar

### 1. Rodar Migrations
```bash
php artisan migrate:fresh
```

### 2. Rodar Seeders
```bash
php artisan db:seed --class=PopulateTicketsSeed  # 10M tickets
php artisan db:seed --class=UserSeeder           # Criar usuários
php artisan db:seed --class=PlanSeeder           # Criar planos
php artisan db:seed --class=RaffleSeeder         # Criar rifas
```

### 3. Criar Compra (Order) para Usuário
```bash
# Criar order manualmente ou via API
# Isso vai gerar wallet_tickets
```

### 4. Testar Aplicação em Rifa
```bash
php artisan db:seed --class=UserApplyToRaffleSeed
```

### 5. Testar via API
```bash
# Login
POST /api/v1/login
{
  "email": "user@example.com",
  "password": "password"
}

# Listar rifas
GET /api/v1/customer/raffles
Authorization: Bearer {token}

# Aplicar tickets
POST /api/v1/customer/raffles/{uuid}/apply-tickets
Authorization: Bearer {token}
{
  "quantity": 5
}

# Ver meus tickets
GET /api/v1/customer/raffles/{uuid}/my-tickets
Authorization: Bearer {token}
```

---

## 📊 Tabelas Envolvidas

### tickets
- Pool global de 10 milhões de números
- Reutilizáveis entre rifas
- Apenas `id` e `number`

### wallet_tickets
- Carteira do usuário
- Armazena quantidade e nível
- Decrementado ao aplicar em rifas

### raffle_tickets
- **TABELA PRINCIPAL** da aplicação
- Relaciona user + raffle + ticket
- Status da participação
- Histórico completo

### raffles
- Configuração das rifas
- Requisitos (nível, quantidade)
- Limites e regras

---

## 🚀 Próximos Passos

### Implementar:
1. ✅ Devolução de tickets ao wallet no cancelamento
2. ⏳ Sistema de sorteio automático
3. ⏳ Notificações de resultados
4. ⏳ Endpoints para administrador gerenciar rifas
5. ⏳ Dashboard de estatísticas de rifas
6. ⏳ Histórico de participações do usuário
7. ⏳ Webhook para processar sorteios
8. ⏳ Sistema de premiação automática

---

## 🔒 Garantias de Integridade

### Banco de Dados:
- ✅ `UNIQUE (raffle_id, ticket_id)` - Evita números duplicados na mesma rifa
- ✅ Foreign keys com constraints
- ✅ Soft deletes preservam histórico
- ✅ Índices otimizados

### Aplicação:
- ✅ Transações completas (rollback automático)
- ✅ Validações em múltiplos níveis
- ✅ Verificação de propriedade (user_id)
- ✅ Verificação de status da rifa
- ✅ Limites configuráveis por rifa

---

## 📝 Notas Importantes

### Performance:
- Pool pré-criado elimina overhead de criação
- Busca aleatória otimizada com índices
- Paginação implementada
- Query log desabilitado em seeds

### Segurança:
- Autenticação obrigatória
- Validação de propriedade
- Limites por usuário
- Status controlados

### Manutenibilidade:
- Código bem documentado
- Services separados
- Controllers enxutos
- Testes facilitados

---

## ✨ Melhorias Implementadas

1. **Arquitetura correta** usando tabela intermediária
2. **Transações completas** com rollback
3. **Validações robustas** em múltiplos níveis
4. **Service layer** separando lógica de negócio
5. **API completa** para frontend consumir
6. **Documentação detalhada** para implementação
7. **Relacionamentos corretos** entre modelos
8. **Métodos auxiliares** para facilitar uso
9. **Scopes úteis** para queries
10. **Tratamento de erros** adequado

---

## 🎉 Resultado Final

Sistema de tickets e rifas **funcionalmente correto**, com:
- ✅ Pool global de tickets reutilizável
- ✅ Carteira de tickets por usuário
- ✅ Aplicação transacional em rifas
- ✅ Validações completas
- ✅ API REST completa
- ✅ Documentação detalhada
- ✅ Pronto para produção (após testes)
