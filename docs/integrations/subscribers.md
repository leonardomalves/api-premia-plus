# 📧 Subscribers (Lead Capture) - Integração Front-end

## 📋 Visão Geral
Instruções para integração do sistema de captação de leads com landing pages e formulários de pré-cadastro.

## 🎯 Fluxo de Lead Capture

### 1. Captura de Lead (Landing Page)
```typescript
interface LeadCaptureRequest {
  email: string;
  name?: string;
  phone?: string;
  // UTM Parameters (opcionais - detectados automaticamente)
  utm_source?: string;
  utm_medium?: string;
  utm_campaign?: string;
  utm_term?: string;
  utm_content?: string;
}

interface LeadCaptureResponse {
  status: 'success';
  message: string;
  data: {
    subscriber: {
      uuid: string;
      email: string;
      name?: string;
      phone?: string;
      status: 'active' | 'unsubscribed';
      utm_data?: {
        source?: string;
        medium?: string;
        campaign?: string;
        term?: string;
        content?: string;
      };
      device_info?: {
        ip: string;
        user_agent: string;
        device_type: 'desktop' | 'mobile' | 'tablet';
      };
    };
  };
  meta: {
    execution_time_ms: number;
  };
}

// Implementação
async function captureLeadFromLandingPage(leadData: LeadCaptureRequest): Promise<LeadCaptureResponse> {
  const response = await fetch('/api/v1/public/leads/capture', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-API-Key': process.env.NEXT_PUBLIC_API_KEY // Necessário para endpoints públicos
    },
    body: JSON.stringify(leadData)
  });
  
  const data = await response.json();
  
  if (data.status === 'success') {
    // Salvar UUID para tracking posterior
    localStorage.setItem('subscriber_uuid', data.data.subscriber.uuid);
    
    // Analytics/Tracking
    gtag('event', 'lead_capture', {
      email: leadData.email,
      utm_source: data.data.subscriber.utm_data?.source
    });
  }
  
  return data;
}
```

### 2. Verificação de Status do Lead
```typescript
interface LeadStatusResponse {
  status: 'success';
  message: string;
  data: {
    subscriber: {
      uuid: string;
      email: string;
      status: 'active' | 'unsubscribed';
      subscribed_at: string;
      unsubscribed_at?: string;
      converted_to_user: boolean;
      converted_at?: string;
    };
  };
  meta: {
    execution_time_ms: number;
  };
}

async function checkLeadStatus(subscriberUuid: string): Promise<LeadStatusResponse> {
  const response = await fetch(`/api/v1/public/leads/status/${subscriberUuid}`, {
    headers: {
      'Accept': 'application/json',
      'X-API-Key': process.env.NEXT_PUBLIC_API_KEY
    }
  });
  
  return await response.json();
}
```

### 3. Unsubscribe (Descadastro)
```typescript
interface UnsubscribeResponse {
  status: 'success';
  message: string;
  data: {
    subscriber: {
      uuid: string;
      email: string;
      status: 'unsubscribed';
      unsubscribed_at: string;
    };
  };
}

async function unsubscribeLead(subscriberUuid: string): Promise<UnsubscribeResponse> {
  const response = await fetch(`/api/v1/public/leads/unsubscribe/${subscriberUuid}`, {
    method: 'DELETE',
    headers: {
      'Accept': 'application/json',
      'X-API-Key': process.env.NEXT_PUBLIC_API_KEY
    }
  });
  
  const data = await response.json();
  
  if (data.status === 'success') {
    // Limpar dados locais
    localStorage.removeItem('subscriber_uuid');
    
    // Analytics
    gtag('event', 'lead_unsubscribe', {
      subscriber_uuid: subscriberUuid
    });
  }
  
  return data;
}
```

## 🎨 Componente de Formulário (React)

### Formulário Básico de Lead Capture
```tsx
import React, { useState } from 'react';

interface LeadFormProps {
  onSuccess?: (subscriber: any) => void;
  onError?: (error: string) => void;
  className?: string;
}

export const LeadCaptureForm: React.FC<LeadFormProps> = ({
  onSuccess,
  onError,
  className = ''
}) => {
  const [formData, setFormData] = useState({
    email: '',
    name: '',
    phone: ''
  });
  const [isLoading, setIsLoading] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setErrors({});
    
    try {
      const response = await captureLeadFromLandingPage(formData);
      
      if (response.status === 'success') {
        onSuccess?.(response.data.subscriber);
        
        // Reset form
        setFormData({ email: '', name: '', phone: '' });
        
        // Show success message
        alert('Cadastro realizado com sucesso! Em breve entraremos em contato.');
      }
    } catch (error: any) {
      const errorData = error.response?.data;
      
      if (errorData?.errors) {
        // Erros de validação
        setErrors(errorData.errors);
      } else if (errorData?.status === 'error' && errorData.message.includes('já cadastrado')) {
        // Email já existe - tratar como sucesso para UX
        alert('Email já cadastrado! Obrigado pelo interesse.');
      } else {
        const message = errorData?.message || 'Erro ao processar cadastro. Tente novamente.';
        onError?.(message);
        alert(message);
      }
    } finally {
      setIsLoading(false);
    }
  };
  
  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
    
    // Limpar erro do campo quando usuário começar a digitar
    if (errors[name]) {
      setErrors(prev => {
        const newErrors = { ...prev };
        delete newErrors[name];
        return newErrors;
      });
    }
  };
  
  return (
    <form onSubmit={handleSubmit} className={className}>
      <div className="mb-4">
        <input
          type="email"
          name="email"
          placeholder="Seu melhor e-mail *"
          value={formData.email}
          onChange={handleInputChange}
          className={`w-full p-3 border rounded-lg ${
            errors.email ? 'border-red-500' : 'border-gray-300'
          }`}
          required
          disabled={isLoading}
        />
        {errors.email && (
          <p className="text-red-500 text-sm mt-1">{errors.email[0]}</p>
        )}
      </div>
      
      <div className="mb-4">
        <input
          type="text"
          name="name"
          placeholder="Seu nome"
          value={formData.name}
          onChange={handleInputChange}
          className={`w-full p-3 border rounded-lg ${
            errors.name ? 'border-red-500' : 'border-gray-300'
          }`}
          disabled={isLoading}
        />
        {errors.name && (
          <p className="text-red-500 text-sm mt-1">{errors.name[0]}</p>
        )}
      </div>
      
      <div className="mb-6">
        <input
          type="tel"
          name="phone"
          placeholder="WhatsApp (opcional)"
          value={formData.phone}
          onChange={handleInputChange}
          className={`w-full p-3 border rounded-lg ${
            errors.phone ? 'border-red-500' : 'border-gray-300'
          }`}
          disabled={isLoading}
        />
        {errors.phone && (
          <p className="text-red-500 text-sm mt-1">{errors.phone[0]}</p>
        )}
      </div>
      
      <button
        type="submit"
        disabled={isLoading}
        className="w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {isLoading ? 'Cadastrando...' : 'Quero Participar!'}
      </button>
      
      <p className="text-xs text-gray-500 mt-4 text-center">
        Ao se cadastrar, você aceita receber comunicações sobre promoções e sorteios.
      </p>
    </form>
  );
};
```

## 🔗 UTM Tracking Automático

### Hook para Captura de UTM Parameters
```typescript
import { useEffect, useState } from 'react';

interface UTMParams {
  utm_source?: string;
  utm_medium?: string;
  utm_campaign?: string;
  utm_term?: string;
  utm_content?: string;
}

export const useUTMTracking = (): UTMParams => {
  const [utmParams, setUtmParams] = useState<UTMParams>({});
  
  useEffect(() => {
    // Capturar UTM parameters da URL atual
    const urlParams = new URLSearchParams(window.location.search);
    const params: UTMParams = {};
    
    ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'].forEach(param => {
      const value = urlParams.get(param);
      if (value) {
        params[param as keyof UTMParams] = value;
      }
    });
    
    // Salvar no localStorage para persistir durante a sessão
    if (Object.keys(params).length > 0) {
      localStorage.setItem('utm_params', JSON.stringify(params));
      setUtmParams(params);
    } else {
      // Tentar recuperar do localStorage
      const saved = localStorage.getItem('utm_params');
      if (saved) {
        setUtmParams(JSON.parse(saved));
      }
    }
  }, []);
  
  return utmParams;
};

// Uso no componente
export const LandingPage: React.FC = () => {
  const utmParams = useUTMTracking();
  
  const handleLeadCapture = async (formData: any) => {
    // Incluir UTM params automaticamente
    const leadData = {
      ...formData,
      ...utmParams
    };
    
    await captureLeadFromLandingPage(leadData);
  };
  
  return (
    <div>
      <LeadCaptureForm onSuccess={handleLeadCapture} />
      
      {/* Debug UTM params em desenvolvimento */}
      {process.env.NODE_ENV === 'development' && (
        <pre className="mt-4 p-2 bg-gray-100 text-xs">
          UTM Params: {JSON.stringify(utmParams, null, 2)}
        </pre>
      )}
    </div>
  );
};
```

## 📊 Analytics e Tracking

### Google Analytics 4 Integration
```typescript
// Declarar gtag globalmente
declare global {
  interface Window {
    gtag: (...args: any[]) => void;
  }
}

interface AnalyticsEvents {
  leadCapture: (data: {
    email: string;
    utm_source?: string;
    utm_campaign?: string;
    device_type?: string;
  }) => void;
  
  leadStatus: (data: {
    subscriber_uuid: string;
    status: string;
  }) => void;
  
  leadUnsubscribe: (data: {
    subscriber_uuid: string;
    reason?: string;
  }) => void;
}

export const analytics: AnalyticsEvents = {
  leadCapture: (data) => {
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('event', 'lead_capture', {
        event_category: 'engagement',
        event_label: data.utm_campaign || 'direct',
        custom_parameters: {
          email_domain: data.email.split('@')[1],
          utm_source: data.utm_source,
          device_type: data.device_type
        }
      });
    }
  },
  
  leadStatus: (data) => {
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('event', 'lead_status_check', {
        event_category: 'engagement',
        custom_parameters: {
          subscriber_uuid: data.subscriber_uuid,
          status: data.status
        }
      });
    }
  },
  
  leadUnsubscribe: (data) => {
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('event', 'lead_unsubscribe', {
        event_category: 'engagement',
        custom_parameters: {
          subscriber_uuid: data.subscriber_uuid,
          reason: data.reason || 'user_initiated'
        }
      });
    }
  }
};
```

### Facebook Pixel Integration
```typescript
// Declarar fbq globalmente
declare global {
  interface Window {
    fbq: (...args: any[]) => void;
  }
}

export const facebookPixel = {
  leadCapture: (email: string) => {
    if (typeof window !== 'undefined' && window.fbq) {
      window.fbq('track', 'Lead', {
        content_name: 'Newsletter Signup',
        content_category: 'Lead Generation',
        value: 1,
        currency: 'BRL'
      });
      
      // Hash do email para Advanced Matching
      window.fbq('track', 'Lead', {}, {
        external_id: btoa(email) // Base64 hash simples
      });
    }
  }
};
```

## 📱 Progressive Web App (PWA)

### Service Worker para Cache de Leads
```typescript
// public/sw.js
const CACHE_NAME = 'premia-leads-v1';
const OFFLINE_LEADS_KEY = 'offline_leads';

// Cache de leads offline
self.addEventListener('fetch', (event) => {
  if (event.request.url.includes('/api/v1/public/leads/capture') && event.request.method === 'POST') {
    event.respondWith(
      fetch(event.request.clone())
        .then(response => {
          if (response.ok) {
            return response;
          }
          throw new Error('Network error');
        })
        .catch(() => {
          // Salvar lead offline para sincronizar depois
          return event.request.json().then(leadData => {
            const offlineLeads = JSON.parse(localStorage.getItem(OFFLINE_LEADS_KEY) || '[]');
            offlineLeads.push({
              ...leadData,
              timestamp: Date.now(),
              synced: false
            });
            localStorage.setItem(OFFLINE_LEADS_KEY, JSON.stringify(offlineLeads));
            
            return new Response(JSON.stringify({
              status: 'success',
              message: 'Lead salvo offline. Será sincronizado quando conectar.',
              data: { subscriber: { uuid: 'offline-' + Date.now() } }
            }), {
              headers: { 'Content-Type': 'application/json' }
            });
          });
        })
    );
  }
});

// Sincronizar leads offline quando voltar online
self.addEventListener('online', () => {
  const offlineLeads = JSON.parse(localStorage.getItem(OFFLINE_LEADS_KEY) || '[]');
  const pendingLeads = offlineLeads.filter(lead => !lead.synced);
  
  pendingLeads.forEach(async (lead) => {
    try {
      await fetch('/api/v1/public/leads/capture', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-API-Key': process.env.NEXT_PUBLIC_API_KEY
        },
        body: JSON.stringify(lead)
      });
      
      lead.synced = true;
    } catch (error) {
      console.error('Erro ao sincronizar lead offline:', error);
    }
  });
  
  localStorage.setItem(OFFLINE_LEADS_KEY, JSON.stringify(offlineLeads));
});
```

## 🎯 Rate Limiting e Tratamento de Erros

### Implementação com Retry Logic
```typescript
interface RetryOptions {
  maxRetries: number;
  delayMs: number;
  backoffMultiplier: number;
}

async function captureLeadWithRetry(
  leadData: LeadCaptureRequest,
  options: RetryOptions = { maxRetries: 3, delayMs: 1000, backoffMultiplier: 2 }
): Promise<LeadCaptureResponse> {
  let lastError: any;
  
  for (let attempt = 0; attempt <= options.maxRetries; attempt++) {
    try {
      return await captureLeadFromLandingPage(leadData);
    } catch (error: any) {
      lastError = error;
      
      // Se for rate limit (429), esperar e tentar novamente
      if (error.response?.status === 429 && attempt < options.maxRetries) {
        const delay = options.delayMs * Math.pow(options.backoffMultiplier, attempt);
        
        console.warn(`Rate limit atingido. Tentando novamente em ${delay}ms...`);
        await new Promise(resolve => setTimeout(resolve, delay));
        continue;
      }
      
      // Se for outro erro, não tentar novamente
      if (error.response?.status !== 429) {
        throw error;
      }
    }
  }
  
  throw lastError;
}
```

## 🔍 Debugging e Monitoring

### Debug Helper
```typescript
const debugLeadCapture = {
  // Verificar rate limits atuais
  checkRateLimit: async () => {
    try {
      const response = await fetch('/api/v1/public/leads/capture', {
        method: 'HEAD', // Apenas headers, sem processar
        headers: { 'X-API-Key': process.env.NEXT_PUBLIC_API_KEY }
      });
      
      console.log('Rate Limit Headers:', {
        remaining: response.headers.get('X-RateLimit-Remaining'),
        limit: response.headers.get('X-RateLimit-Limit'),
        resetTime: response.headers.get('X-RateLimit-Reset')
      });
    } catch (error) {
      console.error('Erro ao verificar rate limit:', error);
    }
  },
  
  // Verificar leads salvos localmente
  checkLocalLeads: () => {
    const uuid = localStorage.getItem('subscriber_uuid');
    const utmParams = localStorage.getItem('utm_params');
    const offlineLeads = localStorage.getItem('offline_leads');
    
    console.log('Lead Data:', {
      subscriberUuid: uuid,
      utmParams: utmParams ? JSON.parse(utmParams) : null,
      offlineLeads: offlineLeads ? JSON.parse(offlineLeads) : []
    });
  },
  
  // Limpar dados de debug
  clearAll: () => {
    localStorage.removeItem('subscriber_uuid');
    localStorage.removeItem('utm_params');
    localStorage.removeItem('offline_leads');
    console.log('Dados de lead capture limpos');
  }
};

// Disponível no console
(window as any).debugLeadCapture = debugLeadCapture;
```

## 🚀 Deploy e Configuração

### Variáveis de Environment
```bash
# .env.local (Next.js)
NEXT_PUBLIC_API_URL=https://api.premiaclub.com.br
NEXT_PUBLIC_API_KEY=sk_api_your_production_key_here

# Para desenvolvimento
NEXT_PUBLIC_API_URL=http://localhost:8000
NEXT_PUBLIC_API_KEY=local-api-key-12345
```

### Configuração de CORS (se necessário)
```php
// config/cors.php
'allowed_origins' => [
    'https://premiaclub.com.br',
    'https://www.premiaclub.com.br',
    'https://landing.premiaclub.com.br',
    // Development
    'http://localhost:3000',
    'http://localhost:3001'
],
```

---

## 📚 Recursos Adicionais

- **Collection Postman:** `docs/postman/collections/Subscribers/`
- **Rate Limits:** Capture (5/min), Status (10/min), Unsubscribe (3/min)
- **Validation Rules:** Ver `app/Http/Requests/Public/CaptureLeadRequest.php`
- **Service Layer:** Ver `app/Services/Customer/SubscriberService.php`