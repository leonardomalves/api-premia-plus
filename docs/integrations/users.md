# 👥 Users & Authentication - Integração Front-end

## 📋 Visão Geral
Instruções para integração do sistema de autenticação e gestão de usuários com o front-end.

## 🔐 Fluxo de Autenticação

### 1. Login do Usuário
```typescript
interface LoginRequest {
  email: string;
  password: string;
  remember?: boolean;
}

interface LoginResponse {
  status: 'success';
  message: string;
  data: {
    token: string;
    user: {
      uuid: string;
      name: string;
      email: string;
      role: 'user' | 'admin';
      profile_photo_url?: string;
    };
  };
  meta: {
    execution_time_ms: number;
  };
}

// Implementação
async function login(credentials: LoginRequest): Promise<LoginResponse> {
  const response = await fetch('/api/v1/auth/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify(credentials)
  });
  
  const data = await response.json();
  
  if (data.status === 'success') {
    // Salvar token para próximas requisições
    localStorage.setItem('auth_token', data.data.token);
    localStorage.setItem('user', JSON.stringify(data.data.user));
  }
  
  return data;
}
```

### 2. Registro de Usuário
```typescript
interface RegisterRequest {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  phone?: string;
  accept_terms: boolean;
}

async function register(userData: RegisterRequest): Promise<LoginResponse> {
  const response = await fetch('/api/v1/auth/register', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify(userData)
  });
  
  return await response.json();
}
```

### 3. Logout
```typescript
async function logout(): Promise<void> {
  const token = localStorage.getItem('auth_token');
  
  await fetch('/api/v1/auth/logout', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  // Limpar dados locais
  localStorage.removeItem('auth_token');
  localStorage.removeItem('user');
}
```

## 👤 Gestão de Perfil

### 1. Obter Perfil do Usuário
```typescript
interface User {
  uuid: string;
  name: string;
  email: string;
  phone?: string;
  role: string;
  email_verified_at?: string;
  profile_photo_url?: string;
  created_at: string;
}

async function getProfile(): Promise<User> {
  const token = localStorage.getItem('auth_token');
  
  const response = await fetch('/api/v1/customer/profile', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  const data = await response.json();
  return data.data.user;
}
```

### 2. Atualizar Perfil
```typescript
interface UpdateProfileRequest {
  name?: string;
  phone?: string;
  // Não incluir email - requer endpoint separado
}

async function updateProfile(updates: UpdateProfileRequest): Promise<User> {
  const token = localStorage.getItem('auth_token');
  
  const response = await fetch('/api/v1/customer/profile', {
    method: 'PATCH',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify(updates)
  });
  
  const data = await response.json();
  
  if (data.status === 'success') {
    // Atualizar dados locais
    localStorage.setItem('user', JSON.stringify(data.data.user));
  }
  
  return data.data.user;
}
```

## 🔧 Interceptor HTTP (Recomendado)

### Axios Setup
```typescript
import axios from 'axios';

// Configuração base
const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Interceptor de requisição - adicionar token automaticamente
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Interceptor de resposta - tratar erros de autenticação
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Token expirado ou inválido
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default api;
```

## 🛡️ Proteção de Rotas

### React Router Example
```typescript
import React from 'react';
import { Navigate } from 'react-router-dom';

interface ProtectedRouteProps {
  children: React.ReactNode;
  requireAdmin?: boolean;
}

export const ProtectedRoute: React.FC<ProtectedRouteProps> = ({ 
  children, 
  requireAdmin = false 
}) => {
  const token = localStorage.getItem('auth_token');
  const userStr = localStorage.getItem('user');
  
  if (!token) {
    return <Navigate to="/login" replace />;
  }
  
  if (requireAdmin && userStr) {
    const user = JSON.parse(userStr);
    if (user.role !== 'admin') {
      return <Navigate to="/dashboard" replace />;
    }
  }
  
  return <>{children}</>;
};
```

### Next.js Middleware Example
```typescript
// middleware.ts
import { NextRequest, NextResponse } from 'next/server';
import { jwtVerify } from 'jose';

export async function middleware(request: NextRequest) {
  const token = request.cookies.get('auth_token')?.value;
  
  if (!token) {
    return NextResponse.redirect(new URL('/login', request.url));
  }
  
  try {
    // Verificar token (opcional - apenas se usando JWT local)
    // const secret = new TextEncoder().encode(process.env.JWT_SECRET);
    // await jwtVerify(token, secret);
    
    return NextResponse.next();
  } catch (error) {
    return NextResponse.redirect(new URL('/login', request.url));
  }
}

export const config = {
  matcher: ['/dashboard/:path*', '/admin/:path*']
};
```

## 📊 Estados de Loading e Erro

### Hook Personalizado (React)
```typescript
import { useState, useCallback } from 'react';

interface UseAuthState {
  user: User | null;
  isLoading: boolean;
  error: string | null;
  login: (credentials: LoginRequest) => Promise<void>;
  logout: () => Promise<void>;
  updateProfile: (updates: UpdateProfileRequest) => Promise<void>;
}

export const useAuth = (): UseAuthState => {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  
  const login = useCallback(async (credentials: LoginRequest) => {
    setIsLoading(true);
    setError(null);
    
    try {
      const response = await api.post('/api/v1/auth/login', credentials);
      const { data } = response.data;
      
      localStorage.setItem('auth_token', data.token);
      localStorage.setItem('user', JSON.stringify(data.user));
      setUser(data.user);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Erro no login');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);
  
  const logout = useCallback(async () => {
    setIsLoading(true);
    try {
      await api.post('/api/v1/auth/logout');
    } finally {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      setUser(null);
      setIsLoading(false);
    }
  }, []);
  
  const updateProfile = useCallback(async (updates: UpdateProfileRequest) => {
    setIsLoading(true);
    setError(null);
    
    try {
      const response = await api.patch('/api/v1/customer/profile', updates);
      const { data } = response.data;
      
      localStorage.setItem('user', JSON.stringify(data.user));
      setUser(data.user);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Erro na atualização');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);
  
  return { user, isLoading, error, login, logout, updateProfile };
};
```

## 🚨 Tratamento de Erros

### Padrão de Resposta de Erro
```typescript
interface ErrorResponse {
  status: 'error';
  message: string;
  errors?: Record<string, string[]>; // Erros de validação
}

// Exemplo de tratamento
try {
  await login(credentials);
} catch (error: any) {
  const errorData: ErrorResponse = error.response?.data;
  
  if (errorData.errors) {
    // Erros de validação (422)
    Object.entries(errorData.errors).forEach(([field, messages]) => {
      console.error(`${field}: ${messages.join(', ')}`);
    });
  } else {
    // Erro geral (401, 500, etc)
    console.error(errorData.message);
  }
}
```

## 🔍 Debugging e Logs

### Console Helper
```typescript
const debugAuth = {
  token: () => console.log('Token:', localStorage.getItem('auth_token')),
  user: () => console.log('User:', JSON.parse(localStorage.getItem('user') || 'null')),
  clear: () => {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user');
    console.log('Auth data cleared');
  }
};

// Disponível no console do navegador
(window as any).debugAuth = debugAuth;
```

## 📱 Considerações Mobile

### Storage Alternativo
```typescript
// Para React Native ou apps híbridos
import AsyncStorage from '@react-native-async-storage/async-storage';

class AuthStorage {
  static async setToken(token: string): Promise<void> {
    await AsyncStorage.setItem('auth_token', token);
  }
  
  static async getToken(): Promise<string | null> {
    return await AsyncStorage.getItem('auth_token');
  }
  
  static async setUser(user: User): Promise<void> {
    await AsyncStorage.setItem('user', JSON.stringify(user));
  }
  
  static async getUser(): Promise<User | null> {
    const userStr = await AsyncStorage.getItem('user');
    return userStr ? JSON.parse(userStr) : null;
  }
  
  static async clear(): Promise<void> {
    await AsyncStorage.multiRemove(['auth_token', 'user']);
  }
}
```

## 🔒 Segurança

### Boas Práticas
1. **Nunca expor tokens** em logs ou console em produção
2. **Usar HTTPS** sempre em produção
3. **Implementar refresh tokens** para sessões longas
4. **Validar tokens** no servidor a cada requisição sensível
5. **Implementar logout** em caso de detecção de atividade suspeita
6. **Usar secure cookies** quando possível (SSR)

### Headers de Segurança
```typescript
// Adicionar aos headers padrão
const securityHeaders = {
  'X-Requested-With': 'XMLHttpRequest',
  'Cache-Control': 'no-cache',
  'Pragma': 'no-cache'
};
```

---

## 📚 Recursos Adicionais

- **Collection Postman:** `docs/postman/collections/Users/`
- **Testes automatizados:** Exemplos de requests/response
- **Environment variables:** Configurações Local/Production
- **Validation rules:** Ver `app/Http/Requests/Auth/`