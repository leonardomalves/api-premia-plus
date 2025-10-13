# 📋 Guia Completo da Collection Postman v5 - API Premia Plus

## 🎯 **Collection COMPLETA com TODOS os Endpoints**

A Collection v5 COMPLETA inclui **TODOS os endpoints** da API Premia Plus organizados por funcionalidade e tipo de usuário.

## 📊 **Estrutura Completa da Collection**

### **🔐 Autenticação (Auth)**
- **Registrar Usuário** - `POST /api/v1/register`
- **Login** - `POST /api/v1/login`
- **Logout** - `POST /api/v1/logout`
- **Refresh Token** - `POST /api/v1/refresh`
- **Meus Dados** - `GET /api/v1/me`
- **Meu Perfil** - `GET /api/v1/profile`
- **Atualizar Perfil** - `PUT /api/v1/profile`
- **Alterar Senha** - `POST /api/v1/change-password`

### **👤 Customer - Perfil**
- **Meu Perfil (Customer)** - `GET /api/v1/customer/me`
- **Atualizar Perfil (Customer)** - `PUT /api/v1/customer/profile`
- **Alterar Senha (Customer)** - `POST /api/v1/customer/change-password`

### **🛒 Customer - Carrinho** ⭐ **NOVO**
- **Adicionar ao Carrinho** - `POST /api/v1/customer/cart/add`
- **Ver Carrinho** - `GET /api/v1/customer/cart`
- **Remover do Carrinho** - `DELETE /api/v1/customer/cart/remove`
- **Limpar Carrinho** - `DELETE /api/v1/customer/cart/clear`
- **Finalizar Compra (Checkout)** - `POST /api/v1/customer/cart/checkout`

### **📦 Customer - Planos**
- **Listar Planos** - `GET /api/v1/customer/plans`
- **Ver Plano Específico** - `GET /api/v1/customer/plans/{uuid}`
- **Planos Promocionais** - `GET /api/v1/customer/plans/promotional/list`
- **Buscar Planos** - `GET /api/v1/customer/plans/search`

### **👥 Customer - Rede**
- **Minha Rede** - `GET /api/v1/customer/network`
- **Meu Patrocinador** - `GET /api/v1/customer/sponsor`
- **Minhas Estatísticas** - `GET /api/v1/customer/statistics`
- **Rede de Usuário Específico** - `GET /api/v1/customer/users/{uuid}/network`
- **Patrocinador de Usuário Específico** - `GET /api/v1/customer/users/{uuid}/sponsor`
- **Estatísticas de Usuário Específico** - `GET /api/v1/customer/users/{uuid}/statistics`

### **👑 Administrator - Usuários**
- **Listar Todos os Usuários** - `GET /api/v1/administrator/users`
- **Ver Usuário Específico** - `GET /api/v1/administrator/users/{uuid}`
- **Criar Usuário** - `POST /api/v1/administrator/users`
- **Atualizar Usuário** - `PUT /api/v1/administrator/users/{uuid}`
- **Deletar Usuário** - `DELETE /api/v1/administrator/users/{uuid}`
- **Rede de Usuário (Admin)** - `GET /api/v1/administrator/users/{uuid}/network`
- **Patrocinador de Usuário (Admin)** - `GET /api/v1/administrator/users/{uuid}/sponsor`
- **Estatísticas de Usuário (Admin)** - `GET /api/v1/administrator/users/{uuid}/statistics`

### **📊 Administrator - Sistema**
- **Estatísticas do Sistema** - `GET /api/v1/administrator/statistics`
- **Dashboard** - `GET /api/v1/administrator/dashboard`
- **Atualização em Massa de Usuários** - `POST /api/v1/administrator/users/bulk-update`
- **Deleção em Massa de Usuários** - `POST /api/v1/administrator/users/bulk-delete`
- **Exportar Usuários** - `POST /api/v1/administrator/users/export`

### **📦 Administrator - Planos**
- **Listar Todos os Planos (Admin)** - `GET /api/v1/administrator/plans`
- **Ver Plano Específico (Admin)** - `GET /api/v1/administrator/plans/{uuid}`
- **Criar Plano** - `POST /api/v1/administrator/plans`
- **Atualizar Plano** - `PUT /api/v1/administrator/plans/{uuid}`
- **Deletar Plano** - `DELETE /api/v1/administrator/plans/{uuid}`
- **Alternar Status do Plano** - `POST /api/v1/administrator/plans/{uuid}/toggle-status`
- **Estatísticas dos Planos** - `GET /api/v1/administrator/plans/statistics/overview`

### **🔧 Sistema - Health & Test**
- **Health Check** - `GET /api/v1/health`
- **Test Endpoint** - `GET /api/v1/test`

## 🔧 **Configuração das Variáveis**

### **Variáveis Globais:**
- `{{base_url}}` - URL base da API (padrão: `http://localhost:8000`)
- `{{access_token}}` - Token de usuário comum
- `{{admin_token}}` - Token de administrador
- `{{user_uuid}}` - UUID do usuário para testes
- `{{plan_uuid}}` - UUID do plano para testes

### **Como Configurar:**
1. **Importar** a collection v5 COMPLETA no Postman
2. **Configurar** `{{base_url}}` se necessário
3. **Fazer Login** como usuário comum e copiar o `access_token`
4. **Fazer Login** como admin e copiar o `admin_token`
5. **Configurar** as variáveis na collection

## 🧪 **Cenários de Teste Completos**

### **Cenário 1: Usuário Comum - Fluxo Completo**
1. **Registrar** → Obter token
2. **Login** → Confirmar autenticação
3. **Listar Planos** → Escolher plano
4. **Adicionar ao Carrinho** → Confirmar
5. **Ver Carrinho** → Verificar item
6. **Checkout** → Finalizar compra
7. **Minha Rede** → Ver patrocinados
8. **Minhas Estatísticas** → Ver dados

### **Cenário 2: Administrador - Gestão Completa**
1. **Login Admin** → Obter admin_token
2. **Dashboard** → Ver estatísticas gerais
3. **Listar Usuários** → Ver todos os usuários
4. **Criar Usuário** → Adicionar novo usuário
5. **Listar Planos** → Ver todos os planos
6. **Criar Plano** → Adicionar novo plano
7. **Estatísticas do Sistema** → Ver métricas

### **Cenário 3: Sistema - Health & Test**
1. **Health Check** → Verificar status da API
2. **Test Endpoint** → Testar conectividade

## 📈 **Funcionalidades por Tipo de Usuário**

### **👤 Usuário Comum (Customer)**
- ✅ **Autenticação** completa
- ✅ **Perfil** próprio
- ✅ **Carrinho** com 1 item não pago
- ✅ **Planos** (apenas leitura)
- ✅ **Rede** de patrocínio
- ✅ **Estatísticas** pessoais

### **👑 Administrador**
- ✅ **Tudo do Customer** +
- ✅ **Gestão de Usuários** (CRUD completo)
- ✅ **Gestão de Planos** (CRUD completo)
- ✅ **Estatísticas do Sistema**
- ✅ **Dashboard Administrativo**
- ✅ **Operações em Massa**
- ✅ **Exportação de Dados**

## 🚀 **Novidades da v5 COMPLETA**

### **✅ Sistema de Carrinho:**
- Carrinho com 1 item não pago por usuário
- Integração Cart-Order
- Checkout completo
- Regras de negócio implementadas

### **✅ Endpoints Administrativos:**
- CRUD completo de usuários
- CRUD completo de planos
- Operações em massa
- Exportação de dados
- Dashboard e estatísticas

### **✅ Organização Completa:**
- Separação por tipo de usuário
- Fluxos lógicos de uso
- Documentação detalhada
- Variáveis configuradas

## 📋 **Total de Endpoints**

- **🔐 Autenticação**: 8 endpoints
- **👤 Customer**: 11 endpoints
- **🛒 Carrinho**: 5 endpoints
- **👑 Administrator**: 20 endpoints
- **🔧 Sistema**: 2 endpoints

**Total: 46 endpoints** organizados em 8 seções principais.

## 🎯 **Próximos Passos**

1. **Importar** a collection v5 COMPLETA no Postman
2. **Configurar** todas as variáveis necessárias
3. **Testar** fluxos de usuário comum
4. **Testar** fluxos de administrador
5. **Validar** sistema de carrinho
6. **Implementar** sistema de pagamento

---

**Collection v5 COMPLETA** está pronta com TODOS os endpoints da API Premia Plus! 🎉
