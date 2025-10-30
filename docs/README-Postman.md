# Postman Collections - Prêmia Club Lead Capture API

Este diretório contém as collections e environments do Postman para testar a API de captura de leads do Prêmia Club.

## 📁 Arquivos Incluídos

### Collections
- **`Premia_Club_Lead_Capture_API.postman_collection.json`** - Collection principal com todos os endpoints

### Environments
- **`Premia_Club_Local_Environment.postman_environment.json`** - Environment para desenvolvimento local
- **`Premia_Club_Production_Environment.postman_environment.json`** - Environment para produção

## 🚀 Como Importar no Postman

### 1. Importar Collection
1. Abra o Postman
2. Clique em **Import** (canto superior esquerdo)
3. Selecione o arquivo `Premia_Club_Lead_Capture_API.postman_collection.json`
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

## 📋 Estrutura da Collection

### 📧 Lead Capture
- **Capturar Lead - Sucesso**: Testa captura com dados válidos
- **Capturar Lead - Email Duplicado**: Testa comportamento com email já existente
- **Capturar Lead - Validation Errors**: Testa validações de campos obrigatórios

### 📊 Lead Status
- **Verificar Status - Sucesso**: Consulta status de um lead existente
- **Verificar Status - Não Encontrado**: Testa consulta com UUID inexistente

### 🚫 Unsubscribe
- **Descadastrar Lead - Sucesso**: Remove lead do sistema
- **Descadastrar Lead - Já Descadastrado**: Testa descadastro de lead já removido
- **Descadastrar Lead - Não Encontrado**: Testa descadastro com UUID inexistente

### 🔄 Rate Limiting Tests
- **Rate Limit - Capture**: Testa rate limiting (5 requests/minuto)

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

## 📝 Próximos Passos

1. **Testes de Carga**: Use Newman CLI para testes automatizados
2. **CI/CD**: Integre os testes no pipeline de deploy
3. **Monitoring**: Configure alertas para rate limiting excessivo
4. **Analytics**: Implemente tracking de conversão de campanhas

## 🤝 Contribuindo

Para adicionar novos testes:
1. Duplique um request existente
2. Modifique payload e URL conforme necessário
3. Ajuste os tests scripts
4. Documente o novo caso de uso
5. Exporte e commite a collection atualizada

---

📚 **Documentação Completa**: Consulte `docs/api-lead-capture.md` para detalhes técnicos da API.