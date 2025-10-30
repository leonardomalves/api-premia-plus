# API de Captura de Leads - Prêmia Club

Esta API permite capturar leads de landing pages com tracking completo de UTM parameters e device fingerprinting.

## Endpoints Disponíveis

### 1. Capturar Lead

**POST** `/api/v1/public/leads/capture`

Captura um novo lead da landing page.

**Rate Limit:** 5 tentativas por minuto por IP

**Payload:**
```json
{
  "name": "João Silva",
  "email": "joao.silva@email.com",
  "phone": "11999887766",
  "preferences": ["sorteios", "promoções"],
  "utm_source": "facebook",
  "utm_medium": "cpc",
  "utm_campaign": "pre-lancamento-2024",
  "utm_term": "sorteios-online",
  "utm_content": "banner-azul",
  "referrer": "https://facebook.com/ads",
  "landing_page": "https://premiaclub.com.br/pre-lancamento"
}
```

**Resposta de Sucesso (201):**
```json
{
  "status": "success",
  "message": "Lead capturado com sucesso!",
  "data": {
    "subscriber_uuid": "123e4567-e89b-12d3-a456-426614174000",
    "email": "joao.silva@email.com",
    "status": "active",
    "tracking_source": "facebook",
    "tracking_campaign": "pre-lancamento-2024",
    "next_steps": {
      "verification_email": "Check your email for verification link",
      "early_access": "You will be notified about early access"
    }
  },
  "meta": {
    "execution_time_ms": 45.23
  }
}
```

**Email Duplicado (200):**
```json
{
  "status": "success",
  "message": "E-mail já cadastrado. Dados atualizados com sucesso.",
  "data": {
    "subscriber_uuid": "123e4567-e89b-12d3-a456-426614174000",
    "status": "active",
    "already_exists": true
  },
  "meta": {
    "execution_time_ms": 12.45
  }
}
```

**Rate Limit Excedido (429):**
```json
{
  "message": "Too Many Attempts.",
  "exception": "Illuminate\\Http\\Exceptions\\ThrottleRequestsException"
}
```

### 2. Verificar Status do Lead

**GET** `/api/v1/public/leads/status/{uuid}`

Verifica o status de um lead pelo UUID.

**Rate Limit:** 10 tentativas por minuto por IP

**Resposta de Sucesso (200):**
```json
{
  "status": "success",
  "message": "Status do lead encontrado.",
  "data": {
    "uuid": "123e4567-e89b-12d3-a456-426614174000",
    "status": "active",
    "subscribed_at": "2024-10-30T14:30:00.000000Z",
    "preferences": ["sorteios", "promoções"]
  }
}
```

**Lead Não Encontrado (404):**
```json
{
  "status": "error",
  "message": "Lead não encontrado.",
  "errors": {
    "uuid": "Subscriber not found"
  }
}
```

### 3. Descadastrar Lead

**DELETE** `/api/v1/public/leads/unsubscribe/{uuid}`

Descadastra um lead pelo UUID (usado em links de email).

**Rate Limit:** 3 tentativas por minuto por IP

**Resposta de Sucesso (200):**
```json
{
  "status": "success",
  "message": "Lead descadastrado com sucesso.",
  "data": {
    "unsubscribed_at": "2024-10-30T15:45:00.000000Z",
    "status": "unsubscribed"
  }
}
```

**Já Descadastrado (200):**
```json
{
  "status": "success",
  "message": "Lead já estava descadastrado.",
  "data": {
    "unsubscribed_at": "2024-10-30T15:45:00.000000Z"
  }
}
```

## Validações

### Captura de Lead

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| name | string | ✅ | 2-100 caracteres |
| email | string | ✅ | Email válido, máx 150 caracteres |
| phone | string | ❌ | 10-20 dígitos |
| preferences | array | ❌ | Array de strings, máx 50 chars cada |
| utm_source | string | ❌ | Máx 100 caracteres |
| utm_medium | string | ❌ | Máx 100 caracteres |
| utm_campaign | string | ❌ | Máx 100 caracteres |
| utm_term | string | ❌ | Máx 100 caracteres |
| utm_content | string | ❌ | Máx 100 caracteres |
| referrer | string | ❌ | URL válida, máx 255 caracteres |
| landing_page | string | ❌ | URL válida, máx 255 caracteres |

## Exemplos de Uso

### JavaScript (Fetch API)

```javascript
// Capturar lead
async function captureLeadFromForm(formData) {
  try {
    const response = await fetch('/api/v1/public/leads/capture', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        name: formData.name,
        email: formData.email,
        phone: formData.phone,
        preferences: formData.preferences,
        // UTM parameters automáticos
        utm_source: new URLSearchParams(window.location.search).get('utm_source'),
        utm_medium: new URLSearchParams(window.location.search).get('utm_medium'),
        utm_campaign: new URLSearchParams(window.location.search).get('utm_campaign'),
        utm_term: new URLSearchParams(window.location.search).get('utm_term'),
        utm_content: new URLSearchParams(window.location.search).get('utm_content'),
        referrer: document.referrer,
        landing_page: window.location.href
      })
    });

    const result = await response.json();
    
    if (response.ok) {
      // Sucesso - redirecionar para página de obrigado
      window.location.href = `/obrigado?uuid=${result.data.subscriber_uuid}`;
    } else {
      // Erro - mostrar mensagem
      console.error('Erro ao capturar lead:', result);
    }
  } catch (error) {
    console.error('Erro de rede:', error);
  }
}

// Verificar status do lead
async function checkLeadStatus(uuid) {
  try {
    const response = await fetch(`/api/v1/public/leads/status/${uuid}`);
    const result = await response.json();
    
    if (response.ok) {
      console.log('Status do lead:', result.data.status);
      return result.data;
    }
  } catch (error) {
    console.error('Erro ao verificar status:', error);
  }
}
```

### cURL

```bash
# Capturar lead
curl -X POST "https://api.premiaclub.com.br/api/v1/public/leads/capture" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "João Silva",
    "email": "joao.silva@email.com",
    "phone": "11999887766",
    "utm_source": "facebook",
    "utm_campaign": "pre-lancamento"
  }'

# Verificar status
curl -X GET "https://api.premiaclub.com.br/api/v1/public/leads/status/123e4567-e89b-12d3-a456-426614174000"

# Descadastrar
curl -X DELETE "https://api.premiaclub.com.br/api/v1/public/leads/unsubscribe/123e4567-e89b-12d3-a456-426614174000"
```

## Tracking Automático

A API captura automaticamente:

- **IP Address**: Para geolocalização
- **User Agent**: Para detecção de dispositivo/browser
- **Device Info**: Mobile, desktop, tablet
- **Browser**: Chrome, Firefox, Safari, etc.
- **Sistema Operacional**: Windows, macOS, Android, iOS
- **Timestamp**: Data/hora da captura
- **Session ID**: Para tracking de sessão (quando disponível)

## Integração com Landing Page

### HTML Form Example

```html
<form id="leadForm" onsubmit="handleFormSubmit(event)">
  <input type="text" name="name" placeholder="Seu nome" required>
  <input type="email" name="email" placeholder="Seu e-mail" required>
  <input type="tel" name="phone" placeholder="Seu telefone">
  
  <label>
    <input type="checkbox" name="preferences" value="sorteios"> Sorteios
  </label>
  <label>
    <input type="checkbox" name="preferences" value="promocoes"> Promoções
  </label>
  
  <button type="submit">Quero Participar!</button>
</form>

<script>
async function handleFormSubmit(event) {
  event.preventDefault();
  
  const formData = new FormData(event.target);
  const preferences = formData.getAll('preferences');
  
  // Capturar UTM parameters da URL
  const urlParams = new URLSearchParams(window.location.search);
  
  const leadData = {
    name: formData.get('name'),
    email: formData.get('email'),
    phone: formData.get('phone'),
    preferences: preferences,
    utm_source: urlParams.get('utm_source'),
    utm_medium: urlParams.get('utm_medium'),
    utm_campaign: urlParams.get('utm_campaign'),
    utm_term: urlParams.get('utm_term'),
    utm_content: urlParams.get('utm_content'),
    referrer: document.referrer,
    landing_page: window.location.href
  };
  
  await captureLeadFromForm(leadData);
}
</script>
```

## URLs de Campanha Recomendadas

Para tracking eficaz, use URLs com UTM parameters:

```
https://premiaclub.com.br/pre-lancamento?utm_source=facebook&utm_medium=cpc&utm_campaign=pre-lancamento-2024&utm_term=sorteios&utm_content=banner-azul

https://premiaclub.com.br/pre-lancamento?utm_source=google&utm_medium=organic&utm_campaign=seo-sorteios

https://premiaclub.com.br/pre-lancamento?utm_source=instagram&utm_medium=social&utm_campaign=stories-promocao&utm_content=video-01
```

## Monitoramento e Analytics

A API armazena dados completos para análises:

- **Conversão por fonte** (Facebook, Google, Instagram, etc.)
- **Performance por campanha**
- **Taxa de conversão por dispositivo/browser**
- **Análise temporal de captura**
- **Tracking de unsubscribe**

Todos os dados ficam disponíveis para dashboards e relatórios administrativos.