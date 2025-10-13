# 📋 Guia da Collection Postman v5 - API Premia Plus (UUID)

## 🎯 **Collection Atualizada para Trabalhar com UUIDs**

A Collection v5 foi atualizada para usar **UUIDs** em vez de IDs numéricos, proporcionando maior segurança e consistência na API.

## 🔄 **Principais Mudanças com UUIDs**

### **🛒 Carrinho - Adicionar Item**
**Antes:**
```json
{
    "plan_id": 1
}
```

**Agora:**
```json
{
    "plan_uuid": "{{plan_uuid}}"
}
```

### **📦 Planos - Endpoints**
- **Listar Planos**: `GET /api/v1/customer/plans` → Retorna planos com UUIDs
- **Ver Plano**: `GET /api/v1/customer/plans/{uuid}` → Usa UUID na URL
- **Buscar Planos**: `GET /api/v1/customer/plans/search` → Retorna UUIDs

### **👥 Usuários - Endpoints**
- **Rede de Usuário**: `GET /api/v1/customer/users/{uuid}/network`
- **Patrocinador**: `GET /api/v1/customer/users/{uuid}/sponsor`
- **Estatísticas**: `GET /api/v1/customer/users/{uuid}/statistics`

## 🔧 **Configuração das Variáveis**

### **Variáveis Globais Atualizadas:**
- `{{base_url}}` - URL base da API
- `{{access_token}}` - Token de usuário comum
- `{{admin_token}}` - Token de administrador
- `{{user_uuid}}` - **UUID do usuário** para testes
- `{{plan_uuid}}` - **UUID do plano** para testes

### **Como Obter UUIDs:**
1. **Listar Planos** → Copiar `uuid` do plano desejado
2. **Listar Usuários** → Copiar `uuid` do usuário desejado
3. **Configurar** as variáveis na collection

## 🧪 **Fluxo de Teste com UUIDs**

### **Cenário 1: Adicionar Plano ao Carrinho**
1. **Login** → Obter `access_token`
2. **Listar Planos** → Ver todos os planos disponíveis
3. **Copiar UUID** do plano desejado
4. **Configurar** `{{plan_uuid}}` na collection
5. **Adicionar ao Carrinho** → Usar `plan_uuid`

### **Cenário 2: Ver Plano Específico**
1. **Listar Planos** → Obter UUID
2. **Configurar** `{{plan_uuid}}`
3. **Ver Plano Específico** → Usar UUID na URL

### **Cenário 3: Administração com UUIDs**
1. **Login Admin** → Obter `admin_token`
2. **Listar Usuários** → Ver UUIDs dos usuários
3. **Configurar** `{{user_uuid}}`
4. **Gerenciar Usuário** → Usar UUID nas operações

## 📊 **Vantagens dos UUIDs**

### **✅ Segurança:**
- UUIDs são mais seguros que IDs sequenciais
- Dificulta enumeração de recursos
- Não revela quantidade de registros

### **✅ Consistência:**
- Todos os recursos usam UUIDs
- Padrão uniforme na API
- Facilita integração entre sistemas

### **✅ Escalabilidade:**
- UUIDs são únicos globalmente
- Permite distribuição de dados
- Evita conflitos em replicação

## 🔄 **Migração de IDs para UUIDs**

### **Endpoints Atualizados:**
- ✅ **Carrinho**: `plan_id` → `plan_uuid`
- ✅ **Planos**: URLs usam `{uuid}` em vez de `{id}`
- ✅ **Usuários**: URLs usam `{uuid}` em vez de `{id}`
- ✅ **Administração**: Todos os endpoints usam UUIDs

### **Respostas da API:**
- ✅ **Planos**: Retornam `uuid` em vez de `id`
- ✅ **Usuários**: Retornam `uuid` em vez de `id`
- ✅ **Carrinho**: Usa `plan_uuid` para referências

## 🚀 **Exemplo Prático**

### **1. Listar Planos:**
```json
GET /api/v1/customer/plans
```
**Resposta:**
```json
{
    "success": true,
    "data": {
        "plans": [
            {
                "uuid": "f9b04594-d6e3-4b67-8d56-bc3c594925ae",
                "name": "Plano Básico",
                "price": "99.90"
            }
        ]
    }
}
```

### **2. Adicionar ao Carrinho:**
```json
POST /api/v1/customer/cart/add
{
    "plan_uuid": "f9b04594-d6e3-4b67-8d56-bc3c594925ae"
}
```

### **3. Ver Plano Específico:**
```json
GET /api/v1/customer/plans/f9b04594-d6e3-4b67-8d56-bc3c594925ae
```

## 📋 **Checklist de Uso**

### **✅ Configuração:**
- [ ] Importar collection v5 COMPLETA
- [ ] Configurar `{{base_url}}`
- [ ] Fazer login e configurar tokens
- [ ] Listar planos e configurar `{{plan_uuid}}`
- [ ] Listar usuários e configurar `{{user_uuid}}`

### **✅ Testes:**
- [ ] Testar carrinho com `plan_uuid`
- [ ] Testar visualização de planos por UUID
- [ ] Testar operações administrativas com UUIDs
- [ ] Validar fluxo completo de compra

---

**Collection v5 com UUIDs** está pronta para uso com maior segurança e consistência! 🎉
