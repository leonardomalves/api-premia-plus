# 📋 Guia da Collection Postman v5 - API Premia Plus

## 🎯 **Nova Funcionalidade: Sistema de Carrinho**

A Collection v5 inclui o **sistema completo de carrinho** com integração Order, permitindo que usuários gerenciem compras de forma intuitiva.

## 🛒 **Novos Endpoints do Carrinho**

### **1. Adicionar ao Carrinho**
```
POST /api/v1/customer/cart/add
```
**Body:**
```json
{
    "plan_id": 1
}
```
**Regra:** Usuário só pode ter 1 item não pago. Se tentar adicionar outro, atualiza o `plan_id` existente.

### **2. Ver Carrinho**
```
GET /api/v1/customer/cart
```
**Resposta:** Retorna o carrinho atual ou vazio se não houver itens.

### **3. Remover do Carrinho**
```
DELETE /api/v1/customer/cart/remove
```
**Ação:** Marca o item como `abandoned`.

### **4. Limpar Carrinho**
```
DELETE /api/v1/customer/cart/clear
```
**Ação:** Marca todos os itens ativos como `abandoned`.

### **5. Finalizar Compra (Checkout)**
```
POST /api/v1/customer/cart/checkout
```
**Ação:** Cria Order a partir do Cart e marca Cart como `completed`.

## 🔄 **Fluxo Completo de Compra**

### **Passo 1: Autenticação**
1. **Registrar** ou **Login** para obter `access_token`
2. Configurar variável `{{access_token}}` na collection

### **Passo 2: Explorar Planos**
1. **Listar Planos** - Ver todos os planos disponíveis
2. **Ver Plano Específico** - Detalhes de um plano
3. **Buscar Planos** - Filtrar por nome/descrição

### **Passo 3: Gerenciar Carrinho**
1. **Adicionar ao Carrinho** - Selecionar plano
2. **Ver Carrinho** - Confirmar item selecionado
3. **Atualizar** - Adicionar outro plano (substitui o anterior)
4. **Remover** - Se não quiser mais o item

### **Passo 4: Finalizar Compra**
1. **Checkout** - Finalizar compra
2. **Resultado** - Order criada e Cart marcado como `completed`

## 📊 **Estrutura da Collection v5**

### **🔐 Autenticação**
- Registrar Usuário
- Login
- Logout

### **👤 Customer - Perfil**
- Meu Perfil
- Atualizar Perfil
- Alterar Senha

### **🛒 Customer - Carrinho** ⭐ **NOVO**
- Adicionar ao Carrinho
- Ver Carrinho
- Remover do Carrinho
- Limpar Carrinho
- Finalizar Compra (Checkout)

### **📦 Customer - Planos**
- Listar Planos
- Ver Plano Específico
- Planos Promocionais
- Buscar Planos

### **👥 Customer - Rede**
- Minha Rede
- Meu Patrocinador
- Minhas Estatísticas

## 🔧 **Configuração das Variáveis**

### **Variáveis Globais:**
- `{{base_url}}` - URL base da API (padrão: `http://localhost:8000`)
- `{{access_token}}` - Token de autenticação
- `{{plan_uuid}}` - UUID do plano para testes

### **Como Configurar:**
1. **Importar** a collection v5 no Postman
2. **Configurar** `{{base_url}}` se necessário
3. **Fazer Login** e copiar o `access_token`
4. **Configurar** `{{access_token}}` na collection

## 🧪 **Cenários de Teste**

### **Cenário 1: Compra Simples**
1. Login → Obter token
2. Listar Planos → Escolher plano
3. Adicionar ao Carrinho → Confirmar
4. Ver Carrinho → Verificar item
5. Checkout → Finalizar compra

### **Cenário 2: Troca de Plano**
1. Adicionar Plano A ao carrinho
2. Adicionar Plano B ao carrinho (substitui A)
3. Ver Carrinho → Confirmar Plano B
4. Checkout → Finalizar

### **Cenário 3: Abandono de Carrinho**
1. Adicionar plano ao carrinho
2. Remover do carrinho
3. Ver Carrinho → Deve estar vazio

## 📈 **Melhorias da v5**

### **✅ Novas Funcionalidades:**
- Sistema completo de carrinho
- Integração Cart-Order
- Regra de 1 item não pago
- Checkout com criação de Order

### **✅ Organização:**
- Seção dedicada ao carrinho
- Fluxo lógico de compra
- Documentação clara dos endpoints

### **✅ Testes:**
- Cenários de teste documentados
- Variáveis configuradas
- Exemplos de request/response

## 🚀 **Próximos Passos**

1. **Importar** a collection v5 no Postman
2. **Configurar** as variáveis necessárias
3. **Testar** o fluxo completo de compra
4. **Validar** a integração Cart-Order
5. **Implementar** sistema de pagamento

---

**Collection v5** está pronta para uso com o sistema completo de carrinho! 🎉
