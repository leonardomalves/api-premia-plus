# Testes - Sistema de Raffle Tickets

## ✅ Testes Criados

### 1. **Testes Feature** - `CustomerRaffleTicketTest.php`
Testa os endpoints da API REST para clientes:

- ✅ `test_customer_can_apply_tickets_to_raffle` - Aplicar tickets em rifa
- ✅ `test_customer_cannot_apply_more_tickets_than_available` - Validação de saldo
- ✅ `test_customer_cannot_exceed_max_tickets_per_user` - Validação de limite por usuário
- ✅ `test_customer_cannot_apply_tickets_with_insufficient_level` - Validação de nível mínimo
- ✅ `test_customer_cannot_apply_tickets_to_inactive_raffle` - Validação de rifa ativa
- ✅ `test_customer_can_list_their_tickets_in_raffle` - Listar tickets do usuário
- ✅ `test_customer_can_cancel_their_pending_tickets` - Cancelar tickets pendentes
- ✅ `test_customer_cannot_cancel_confirmed_tickets` - Validação de status confirmed
- ✅ `test_customer_can_list_all_available_raffles` - Listar rifas disponíveis
- ✅ `test_customer_can_view_raffle_details` - Visualizar detalhes da rifa
- ✅ `test_validation_errors_for_applying_tickets` - Validações de entrada
- ✅ `test_unauthenticated_user_cannot_access_raffle_ticket_endpoints` - Autenticação

**Total: 12 testes**

### 2. **Testes Unit** - `RaffleTicketServiceTest.php`
Testa a camada de serviço e lógica de negócio:

- ✅ `test_service_can_apply_tickets_to_raffle_successfully` - Aplicação bem-sucedida
- ✅ `test_service_throws_exception_when_user_has_no_tickets` - Sem tickets
- ✅ `test_service_throws_exception_when_user_has_insufficient_tickets` - Saldo insuficiente
- ✅ `test_service_throws_exception_when_raffle_is_not_active` - Rifa inativa
- ✅ `test_service_throws_exception_when_exceeding_max_tickets_per_user` - Excede máximo
- ✅ `test_service_respects_max_tickets_when_user_already_has_tickets` - Contagem acumulada
- ✅ `test_service_can_cancel_pending_tickets` - Cancelamento bem-sucedido
- ✅ `test_service_cannot_cancel_confirmed_tickets` - Validação de cancelamento
- ✅ `test_service_can_get_user_tickets_in_raffle` - Consulta de tickets
- ✅ `test_service_uses_tickets_with_appropriate_level` - Nível apropriado
- ✅ `test_service_rollback_on_failure` - Transação rollback

**Total: 11 testes**

### 3. **Testes Unit** - `TicketModelTest.php`
Testa o modelo Ticket (pool global):

- ✅ `test_ticket_can_be_created_with_only_number` - Criação simples
- ✅ `test_ticket_number_is_unique` - Unicidade do número
- ✅ `test_ticket_has_raffle_tickets_relationship` - Relacionamento hasMany
- ✅ `test_ticket_has_raffles_relationship` - Relacionamento belongsToMany
- ✅ `test_is_available_returns_true_for_unused_ticket` - Helper isAvailable()
- ✅ `test_is_available_returns_false_for_used_ticket` - Helper isAvailable() negativo
- ✅ `test_is_applied_in_raffle_method` - Helper isAppliedInRaffle()
- ✅ `test_scope_available_filters_only_unused_tickets` - Scope available()
- ✅ `test_scope_applied_filters_only_used_tickets` - Scope applied()
- ✅ `test_ticket_can_be_soft_deleted` - Soft delete
- ✅ `test_ticket_can_be_restored_after_soft_delete` - Restore
- ✅ `test_ticket_factory_generates_valid_tickets` - Factory
- ✅ `test_ticket_does_not_have_direct_user_or_raffle_relationships` - Arquitetura correta
- ✅ `test_multiple_tickets_can_be_applied_to_same_raffle` - Múltiplos tickets
- ✅ `test_same_ticket_can_be_applied_to_different_raffles` - Reutilização

**Total: 15 testes**

### 4. **Testes Unit** - `RaffleTicketModelTest.php`
Testa o modelo RaffleTicket (tabela intermediária):

- ✅ `test_raffle_ticket_can_be_created` - Criação básica
- ✅ `test_raffle_ticket_has_user_relationship` - Relacionamento User
- ✅ `test_raffle_ticket_has_raffle_relationship` - Relacionamento Raffle
- ✅ `test_raffle_ticket_has_ticket_relationship` - Relacionamento Ticket
- ✅ `test_status_constants_are_defined` - Constantes STATUS_*
- ✅ `test_is_pending_method` - Helper isPending()
- ✅ `test_is_confirmed_method` - Helper isConfirmed()
- ✅ `test_is_winner_method` - Helper isWinner()
- ✅ `test_mark_as_confirmed_method` - Setter markAsConfirmed()
- ✅ `test_mark_as_winner_method` - Setter markAsWinner()
- ✅ `test_scope_pending_filters_correctly` - Scope pending()
- ✅ `test_scope_confirmed_filters_correctly` - Scope confirmed()
- ✅ `test_scope_winner_filters_correctly` - Scope winner()
- ✅ `test_raffle_ticket_can_be_soft_deleted` - Soft delete
- ✅ `test_raffle_ticket_can_be_restored` - Restore
- ✅ `test_uuid_is_automatically_generated` - UUID auto-generation
- ✅ `test_factory_creates_valid_raffle_tickets` - Factory
- ✅ `test_user_can_have_multiple_tickets_in_same_raffle` - Múltiplos por usuário
- ✅ `test_raffle_ticket_status_transitions` - Transições de status
- ✅ `test_timestamps_are_cast_to_datetime` - Casting timestamps
- ✅ `test_eager_loading_relationships` - Eager loading

**Total: 21 testes**

## 📊 Resumo

- **Total de Testes**: 59 testes
- **Cobertura**:
  - ✅ Endpoints API REST (12 testes)
  - ✅ Lógica de Negócio/Service (11 testes)
  - ✅ Modelos e Relacionamentos (36 testes)
  - ✅ Validações e Regras (integrado em todos)
  - ✅ Transações e Rollback (1 teste específico)
  - ✅ Autenticação e Autorização (1 teste)

## 🔧 Correções Aplicadas

### 1. **RaffleTicketFactory.php** - CRIADO
- Factory completo para RaffleTicket
- States: pending(), confirmed(), winner()

### 2. **RaffleTicket.php** - ATUALIZADO
- Adicionado `use HasFactory`
- Adicionado método `boot()` para gerar UUID automaticamente
- Importado `Illuminate\Support\Str`

### 3. **WalletTicketFactory.php** - CORRIGIDO
- Removidos campos inválidos (transaction_type, amount, balance_before, balance_after)
- Adicionados campos corretos (uuid, plan_id, ticket_level, total_tickets, status, expiration_date)
- States: active(), expired()

## ⚠️ Problemas Identificados (Ainda não Resolvidos)

### 1. **Rotas Customer** - Retornando 404
As rotas dos endpoints customer não estão sendo encontradas:
- `POST /api/v1/customer/raffles/{uuid}/tickets`
- `GET /api/v1/customer/raffles/{uuid}/my-tickets`
- `DELETE /api/v1/customer/raffles/{uuid}/tickets`
- `GET /api/v1/customer/raffles`
- `GET /api/v1/customer/raffles/{uuid}`

**Causa Provável**: As rotas podem não estar registradas corretamente no arquivo `routes/api/v1/customer.php` ou o arquivo não está sendo carregado.

### 2. **Validação de tickets_required** no Service
O serviço está validando `tickets_required` da rifa, mas essa não deveria ser uma restrição do lado do cliente. Essa validação pode estar impedindo aplicações de tickets válidas.

## 🔍 Próximos Passos

1. **Verificar rotas customer**:
   - Confirmar que `routes/api/v1/customer.php` existe
   - Verificar se está sendo incluído em `routes/api.php`
   - Verificar middleware de autenticação

2. **Executar testes novamente**:
   ```bash
   php artisan test --filter="TicketModel"
   ```

3. **Ajustar validações** no `RaffleTicketService` se necessário

4. **Documentar resultados finais**

## 📝 Comandos para Executar

```bash
# Executar todos os testes de raffle tickets
php artisan test --filter="CustomerRaffleTicket|RaffleTicketService|TicketModel|RaffleTicketModel"

# Executar apenas testes de model (que estão passando)
php artisan test --filter="TicketModel"

# Executar apenas testes unitários
php artisan test --testsuite=Unit

# Executar apenas testes feature
php artisan test --testsuite=Feature
```
