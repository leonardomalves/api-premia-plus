# 🛣️ Estrutura de Rotas - API Premia Plus

## 📋 **Estrutura de Paths Proposta**

### **🎯 Padrão de URLs:**
```
base_url/api/v{version}/{user_type}/{endpoint}
```

### **📁 Estrutura de Arquivos:**
```
routes/api/
├── v1/
│   ├── auth.php           # Autenticação (sem prefixo)
│   ├── customer.php       # Customer routes
│   ├── administrator.php  # Administrator routes
│   └── shared.php         # Rotas compartilhadas
├── v2/                    # Futura versão
│   ├── auth.php
│   ├── customer.php
│   └── administrator.php
└── api.php               # Arquivo principal
```

## 🔐 **Rotas de Autenticação (Sem Prefixo)**
```php
// routes/api/v1/auth.php
Route::prefix('v1')->group(function () {
    // Autenticação (sem prefixo de usuário)
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    
    // Health check
    Route::get('/health', [HealthController::class, 'check']);
    Route::get('/test', [TestController::class, 'index']);
});
```

## 👤 **Rotas de Customer**
```php
// routes/api/v1/customer.php
Route::prefix('v1/customer')->middleware('auth:sanctum')->group(function () {
    // Perfil do usuário
    Route::get('/me', [CustomerController::class, 'show']);
    Route::put('/profile', [CustomerController::class, 'updateProfile']);
    Route::post('/change-password', [CustomerController::class, 'changePassword']);
    
    // Rede e estatísticas
    Route::get('/network', [CustomerController::class, 'network']);
    Route::get('/sponsor', [CustomerController::class, 'sponsor']);
    Route::get('/statistics', [CustomerController::class, 'statistics']);
    
    // Usuários específicos (com permissão)
    Route::get('/users/{uuid}/network', [CustomerController::class, 'userNetwork']);
    Route::get('/users/{uuid}/sponsor', [CustomerController::class, 'userSponsor']);
    Route::get('/users/{uuid}/statistics', [CustomerController::class, 'userStatistics']);
});
```

## 🔧 **Rotas de Administrator**
```php
// routes/api/v1/administrator.php
Route::prefix('v1/administrator')->middleware(['auth:sanctum', 'admin'])->group(function () {
    // Gerenciamento de usuários
    Route::get('/users', [AdministratorController::class, 'index']);
    Route::get('/users/{uuid}', [AdministratorController::class, 'show']);
    Route::post('/users', [AdministratorController::class, 'store']);
    Route::put('/users/{uuid}', [AdministratorController::class, 'update']);
    Route::delete('/users/{uuid}', [AdministratorController::class, 'destroy']);
    
    // Rede e estatísticas de usuários
    Route::get('/users/{uuid}/network', [AdministratorController::class, 'network']);
    Route::get('/users/{uuid}/sponsor', [AdministratorController::class, 'sponsor']);
    Route::get('/users/{uuid}/statistics', [AdministratorController::class, 'statistics']);
    
    // Sistema
    Route::get('/statistics', [AdministratorController::class, 'systemStatistics']);
    Route::get('/dashboard', [AdministratorController::class, 'dashboard']);
    
    // Operações em massa
    Route::post('/users/bulk-update', [AdministratorController::class, 'bulkUpdate']);
    Route::post('/users/bulk-delete', [AdministratorController::class, 'bulkDelete']);
    Route::post('/users/export', [AdministratorController::class, 'exportUsers']);
});
```

## 🌐 **Rotas Compartilhadas**
```php
// routes/api/v1/shared.php
Route::prefix('v1')->group(function () {
    // Rotas públicas
    Route::get('/health', [HealthController::class, 'check']);
    Route::get('/test', [TestController::class, 'index']);
    
    // Rotas com autenticação (qualquer usuário)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/profile', [AuthController::class, 'profile']);
    });
});
```

## 📊 **Exemplos de URLs Finais**

### **🔐 Autenticação:**
```
POST /api/v1/register
POST /api/v1/login
POST /api/v1/logout
POST /api/v1/refresh
```

### **👤 Customer:**
```
GET    /api/v1/customer/me
PUT    /api/v1/customer/profile
POST   /api/v1/customer/change-password
GET    /api/v1/customer/network
GET    /api/v1/customer/sponsor
GET    /api/v1/customer/statistics
GET    /api/v1/customer/users/{uuid}/network
GET    /api/v1/customer/users/{uuid}/sponsor
GET    /api/v1/customer/users/{uuid}/statistics
```

### **🔧 Administrator:**
```
GET    /api/v1/administrator/users
GET    /api/v1/administrator/users/{uuid}
POST   /api/v1/administrator/users
PUT    /api/v1/administrator/users/{uuid}
DELETE /api/v1/administrator/users/{uuid}
GET    /api/v1/administrator/users/{uuid}/network
GET    /api/v1/administrator/users/{uuid}/sponsor
GET    /api/v1/administrator/users/{uuid}/statistics
GET    /api/v1/administrator/statistics
GET    /api/v1/administrator/dashboard
POST   /api/v1/administrator/users/bulk-update
POST   /api/v1/administrator/users/bulk-delete
POST   /api/v1/administrator/users/export
```

### **🌐 Compartilhadas:**
```
GET    /api/v1/health
GET    /api/v1/test
GET    /api/v1/me
GET    /api/v1/profile
```

## 🎯 **Benefícios da Estrutura**

### **✅ Organização Clara**
- **Customer**: `/api/v1/customer/*`
- **Administrator**: `/api/v1/administrator/*`
- **Auth**: `/api/v1/*` (sem prefixo)
- **Shared**: `/api/v1/*` (compartilhadas)

### **✅ Versionamento**
- **v1**: Versão atual
- **v2**: Futura versão (quando necessário)
- **Backward compatibility**: Manter v1 funcionando

### **✅ Middleware Específico**
- **Customer**: `auth:sanctum`
- **Administrator**: `auth:sanctum` + `admin`
- **Auth**: Sem middleware (público)
- **Shared**: `auth:sanctum` (qualquer usuário)

### **✅ Escalabilidade**
- **Novos tipos**: Fácil adicionar novos tipos de usuário
- **Novas versões**: Estrutura preparada para v2, v3, etc.
- **Modular**: Cada tipo de usuário isolado

## 🚀 **Implementação**

### **1. Criar Estrutura de Diretórios**
```bash
mkdir -p routes/api/v1
```

### **2. Criar Arquivos de Rotas**
```bash
# Criar arquivos
touch routes/api/v1/auth.php
touch routes/api/v1/customer.php
touch routes/api/v1/administrator.php
touch routes/api/v1/shared.php
```

### **3. Atualizar api.php Principal**
```php
// routes/api.php
<?php

use Illuminate\Support\Facades\Route;

// API v1
Route::prefix('v1')->group(function () {
    require __DIR__.'/api/v1/auth.php';
    require __DIR__.'/api/v1/customer.php';
    require __DIR__.'/api/v1/administrator.php';
    require __DIR__.'/api/v1/shared.php';
});

// Futuras versões
// Route::prefix('v2')->group(function () {
//     require __DIR__.'/api/v2/auth.php';
//     require __DIR__.'/api/v2/customer.php';
//     require __DIR__.'/api/v2/administrator.php';
// });
```

## 🎉 **Resultado Final**

Uma estrutura de rotas **profissional e organizada** que:

- ✅ **Separa por tipo de usuário**: Customer vs Administrator
- ✅ **Suporta versionamento**: v1, v2, etc.
- ✅ **Middleware específico**: Por tipo de usuário
- ✅ **URLs intuitivas**: Fácil de entender e usar
- ✅ **Escalável**: Preparada para crescimento

**🚀 API Premia Plus - Rotas Organizadas e Profissionais!**
