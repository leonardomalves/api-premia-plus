# Postman Collections - Prêmia Club API

Este diretório contém as collections e environments do Postman organizadas por domínio para testar toda a API do sistema Prêmia Club.

## 📁 Estrutura Organizada por Domínio

### Collections por Domínio
- **`collections/Users/`** - Autenticação e gestão de usuários
- **`collections/Subscribers/`** - Sistema de captação de leads
- **`collections/Raffles/`** - Rifas e sorteios *(em desenvolvimento)*
- **`collections/Commissions/`** - Sistema de comissões *(em desenvolvimento)*  
- **`collections/Orders/`** - Pedidos e carrinho de compras

### Environments
- **`Premia_Club_Local_Environment.postman_environment.json`** - Environment para desenvolvimento local
- **`Premia_Club_Production_Environment.postman_environment.json`** - Environment para produção

### Documentação
- **`collections/README.md`** - Guia completo da nova organização por domínios

## 🚀 Como Importar no Postman

### 1. Importar Collections por Domínio
1. Abra o Postman
2. Clique em **Import** (canto superior esquerdo)
3. Selecione as collections desejadas dos diretórios por domínio:
   - `collections/Users/Premia_Club_Users_API.postman_collection.json`
   - `collections/Subscribers/Premia_Club_Lead_Capture_API.postman_collection.json`
   - `collections/Orders/Premia_Club_Orders_API.postman_collection.json`
4. Clique em **Import**

### 2. Importar Environments
1. No Postman, vá em **Environments** (barra lateral esquerda)
2. Clique em **Import**
3. Selecione os arquivos de environment:
   - `Premia_Club_Local_Environment.postman_environment.json`
   - `Premia_Club_Production_Environment.postman_environment.json`
4. Clique em **Import**

### 3. Configurar Environment
1. Selecione o environment desejado no dropdown (canto superior direito)
2. Para desenvolvimento local: **Prêmia Club - Local Development**
3. Para produção: **Prêmia Club - Production**

### 4. Ordem Recomendada de Importação
Para melhor experiência de teste:
1. **Users** - Para autenticação (obrigatório primeiro)
2. **Subscribers** - Para funcionalidades públicas
3. **Orders** - Para funcionalidades de compra (requer login)
4. **Raffles** e **Commissions** - Conforme necessário

## 📋 Collections Disponíveis

### � Users & Authentication
**Localização:** `collections/Users/`
- ✅ **Login/Logout**: Autenticação completa com tokens
- ✅ **Register**: Registro de novos usuários
- ✅ **Profile Management**: Gestão de perfil do usuário
- ✅ **Admin CRUD**: Gestão administrativa de usuários
- ✅ **Password Recovery**: Recuperação de senha

### 📧 Subscribers (Lead Capture)
**Localização:** `collections/Subscribers/`
- ✅ **Capturar Lead**: Captação com tracking UTM
- ✅ **Verificar Status**: Consulta status de leads
- ✅ **Unsubscribe**: Sistema de descadastro
- ✅ **Rate Limiting**: Controle de taxa implementado

### � Orders & Cart
**Localização:** `collections/Orders/`
- ✅ **Shopping Cart**: Gestão de carrinho de compras
- ✅ **Order Creation**: Criação de pedidos a partir do carrinho
- ✅ **Order History**: Histórico de compras do usuário
- ✅ **Admin Management**: Gestão administrativa de pedidos

### 🎯 Raffles *(Em Desenvolvimento)*
**Localização:** `collections/Raffles/`
- 🚧 **Public Listings**: Listagem pública de rifas
- 🚧 **Ticket Purchase**: Compra de tickets
- 🚧 **Draw Results**: Resultados dos sorteios
- 🚧 **Admin CRUD**: Gestão de rifas

### � Commissions *(Em Desenvolvimento)*
**Localização:** `collections/Commissions/`
- 🚧 **Reports**: Relatórios de comissões
- 🚧 **Earnings History**: Histórico de ganhos
- 🚧 **Level Configuration**: Configuração de níveis

## 🔧 Configurações Automáticas

### Variables Dinâmicas
A collection utiliza variáveis dinâmicas que são automaticamente configuradas:

- **`random_email`**: Email aleatório para evitar duplicatas
- **`subscriber_uuid`**: UUID do subscriber criado (usado em testes subsequentes)
- **`current_timestamp`**: Timestamp atual para tracking
- **`rate_limit_email`**: Email específico para testes de rate limiting

### Pre-request Scripts
Cada request possui scripts que:
- Geram emails únicos automaticamente
- Configuram timestamps para UTM tracking
- Definem headers padrão (Accept: application/json)

### Tests Automáticos
Cada request inclui testes que verificam:
- Status codes corretos
- Estrutura das respostas
- Performance (tempo de resposta)
- Validação de dados retornados

## 🏃‍♂️ Executando os Testes

### Execução Individual
1. Selecione o request desejado
2. Clique em **Send**
3. Veja os resultados dos testes na aba **Test Results**

### Execução da Collection Completa
1. Clique com botão direito na collection
2. Selecione **Run collection**
3. Configure os parâmetros:
   - **Iterations**: 1
   - **Delay**: 1000ms (para evitar rate limiting)
4. Clique em **Run Prêmia Club - Lead Capture API**

### Collection Runner (Recomendado)
Para testes automatizados completos:
1. Vá em **Runner** (barra lateral)
2. Selecione a collection **Prêmia Club - Lead Capture API**
3. Selecione o environment apropriado
4. Configure:
   - **Iterations**: 1
   - **Delay**: 2000ms
   - **Data**: None
5. Clique em **Start Run**

## 📊 Interpretando os Resultados

### Status Codes Esperados
- **201**: Lead capturado com sucesso
- **200**: Email já existe, dados atualizados / Status encontrado / Unsubscribe realizado
- **404**: Lead não encontrado
- **422**: Erros de validação
- **429**: Rate limit excedido

### Estrutura das Respostas

#### Sucesso (2xx)
```json
{
  "status": "success",
  "message": "Mensagem descritiva",
  "data": { ... },
  "meta": {
    "execution_time_ms": 45.23
  }
}
```

#### Erro (4xx/5xx)
```json
{
  "status": "error",
  "message": "Mensagem de erro",
  "errors": { ... }
}
```

## 🛠️ Troubleshooting

### Servidor Local Não Responde
1. Verifique se o Laravel está rodando: `php artisan serve`
2. Confirme a URL no environment: `http://localhost:8000`
3. Verifique se não há conflitos de porta

### Rate Limiting
Se receber erro 429:
1. Aguarde 1 minuto antes de tentar novamente
2. Use diferentes IPs para testes paralelos
3. Ajuste o delay no Collection Runner

### Testes Falhando
1. Verifique se o banco de dados está configurado
2. Execute as migrations: `php artisan migrate`
3. Execute os seeders se necessário: `php artisan db:seed`

### Variables Não Definidas
Se variáveis como `{{subscriber_uuid}}` não estão definidas:
1. Execute primeiro o request "Capturar Lead - Sucesso"
2. Verifique se os scripts pre-request estão habilitados
3. Confirme se o environment está selecionado

## 📈 Monitoring & Logs

### Durante Desenvolvimento
- Monitore os logs do Laravel: `tail -f storage/logs/laravel.log`
- Use `php artisan telescope` para debugging avançado
- Verifique queries no banco: `php artisan db:monitor`

### Métricas de Performance
Os testes incluem verificações de performance:
- Lead Capture: < 2000ms
- Status Check: < 1000ms
- Unsubscribe: < 1000ms

## 🔐 Segurança

### Rate Limiting
A API implementa rate limiting por IP:
- **Capture**: 5 tentativas/minuto
- **Status**: 10 tentativas/minuto
- **Unsubscribe**: 3 tentativas/minuto

### Dados Sensíveis
- Não inclua dados reais de produção nos testes
- Use emails de teste (@email.com)
- Telefones fictícios apenas

## 🏗️ Nova Organização por Domínio (v2.0)

### Benefícios da Reorganização
- **Modularidade**: Cada domínio pode ser testado independentemente
- **Escalabilidade**: Fácil adição de novos domínios e funcionalidades
- **Manutenibilidade**: Estrutura clara e organizada por contexto de negócio
- **Colaboração**: Equipes podem trabalhar em collections específicas

### Migração da Versão Anterior
Se você estava usando a collection anterior (`Premia_Club_Lead_Capture_API.postman_collection.json`):
1. A funcionalidade de lead capture agora está em `collections/Subscribers/`
2. Importe a nova estrutura seguindo as instruções acima
3. As variáveis e environments permanecem compatíveis
4. Todos os testes existentes foram preservados e melhorados

### Vantagens dos Scripts Automáticos
Cada collection inclui:
- **Auto-authentication**: Tokens salvos automaticamente
- **Dynamic Variables**: UUIDs e IDs extraídos das respostas
- **Comprehensive Tests**: Validação completa de estruturas
- **Performance Monitoring**: Métricas de tempo de execução incluídas

## 📝 Próximos Passos

### Desenvolvimento das Collections
1. **Raffles**: Implementar endpoints de rifas e sorteios
2. **Commissions**: Sistema completo de comissões
3. **Webhooks**: Collection para testes de notificações
4. **Analytics**: Endpoints de relatórios e métricas

### Automação e CI/CD
1. **Newman CLI**: Testes automatizados no pipeline
2. **Collection Monitoring**: Monitoramento contínuo via Postman
3. **Data-driven Testing**: Implementar testes com datasets
4. **Performance Testing**: Testes de carga automatizados

## 🤝 Contribuindo

### Para Adicionar Nova Collection
1. Crie o diretório do domínio em `collections/[Domain]/`
2. Use a estrutura padrão das collections existentes
3. Inclua scripts de teste automatizados
4. Documente os endpoints no README do domínio
5. Atualize este README principal

### Para Modificar Collections Existentes
1. Mantenha a compatibilidade com environments
2. Preserve os scripts de teste existentes
3. Documente breaking changes
4. Teste em ambos os environments (Local/Production)

### Padrões de Nomenclatura
- **Collections**: `Premia_Club_[Domain]_API.postman_collection.json`
- **Folders**: Usar emojis e nomes descritivos (`🛒 Shopping Cart`)
- **Requests**: Ação + Cenário (`Get Cart`, `Add Item - Success`)
- **Variables**: snake_case (`auth_token`, `user_uuid`)

---

📚 **Documentação Completa**: 
- Consulte `collections/README.md` para guia detalhado da nova organização
- Consulte `docs/api-lead-capture.md` para detalhes técnicos da API de leads