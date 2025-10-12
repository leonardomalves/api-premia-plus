# API Premia Plus - Documentação

## Visão Geral

A API Premia Plus é um sistema de API RESTful desenvolvido em Laravel 12 com Sanctum para autenticação. O sistema permite gerenciamento de usuários, sistema de patrocínio/afiliação e controle de roles.

## Base URL

```
http://localhost:8000/api/v1
```

## Autenticação

A API utiliza Laravel Sanctum para autenticação via tokens. Inclua o token no header:

```
Authorization: Bearer {seu_token}
```

## Endpoints

### 🔐 Autenticação

#### POST /register
Registra um novo usuário.

**Body:**
```json
{
    "name": "João Silva",
    "email": "joao@example.com",
    "password": "senha123",
    "password_confirmation": "senha123",
    "phone": "11999999999",
    "sponsor_id": 1
}
```

**Response:**
```json
{
    "message": "Usuário registrado com sucesso",
    "user": {...},
    "access_token": "token_aqui",
    "token_type": "Bearer"
}
```

#### POST /login
Faz login do usuário.

**Body:**
```json
{
    "email": "joao@example.com",
    "password": "senha123"
}
```

#### POST /logout
Faz logout do usuário (requer autenticação).

#### GET /me
Retorna dados do usuário autenticado.

#### GET /profile
Retorna perfil completo do usuário.

#### PUT /profile
Atualiza perfil do usuário.

**Body:**
```json
{
    "name": "Novo Nome",
    "phone": "11888888888"
}
```

#### POST /change-password
Altera senha do usuário.

**Body:**
```json
{
    "current_password": "senha_atual",
    "password": "nova_senha",
    "password_confirmation": "nova_senha"
}
```

### 👥 Usuários

#### GET /users
Lista todos os usuários (admin apenas).

#### GET /users/{id}
Retorna dados de um usuário específico.

#### PUT /users/{id}
Atualiza dados de um usuário (admin apenas).

#### DELETE /users/{id}
Remove um usuário (admin apenas).

#### GET /users/{id}/network
Retorna rede de usuários patrocinados.

#### GET /users/{id}/sponsor
Retorna dados do patrocinador.

#### GET /users/{id}/statistics
Retorna estatísticas do usuário.

### 🌐 Rede de Afiliação

#### GET /my-network
Retorna sua rede de usuários patrocinados.

#### GET /my-sponsor
Retorna dados do seu patrocinador.

#### GET /my-statistics
Retorna suas estatísticas.

### 🔧 Utilitários

#### GET /health
Verifica status da API.

#### GET /test
Endpoint de teste.

## Códigos de Status

- `200` - Sucesso
- `201` - Criado com sucesso
- `400` - Dados inválidos
- `401` - Não autenticado
- `403` - Acesso negado
- `404` - Não encontrado
- `422` - Erro de validação
- `500` - Erro interno

## Exemplos de Uso

### 1. Registrar Usuário

```bash
curl -X POST http://localhost:8000/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "João Silva",
    "email": "joao@example.com",
    "password": "senha123",
    "password_confirmation": "senha123"
  }'
```

### 2. Fazer Login

```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "joao@example.com",
    "password": "senha123"
  }'
```

### 3. Acessar Dados Protegidos

```bash
curl -X GET http://localhost:8000/api/v1/me \
  -H "Authorization: Bearer {seu_token}"
```

## Estrutura de Dados

### User
```json
{
    "id": 1,
    "name": "João Silva",
    "email": "joao@example.com",
    "phone": "11999999999",
    "role": "user",
    "status": "active",
    "sponsor_id": null,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
}
```

## Roles e Permissões

- **user**: Usuário comum
- **admin**: Administrador (acesso total)
- **moderator**: Moderador (acesso limitado)

## Status de Usuário

- **active**: Usuário ativo
- **inactive**: Usuário inativo
- **suspended**: Usuário suspenso

## Sistema de Patrocínio

O sistema permite que usuários tenham patrocinadores (sponsor_id) e patrocinem outros usuários, criando uma rede de afiliação.

## Middleware

- `auth:sanctum`: Requer autenticação
- `admin`: Requer role de administrador

## Configuração

1. Instale as dependências: `composer install`
2. Configure o banco de dados no `.env`
3. Execute as migrações: `php artisan migrate`
4. Inicie o servidor: `php artisan serve`

## Testes

Para testar a API, você pode usar:

- **Postman**
- **Insomnia**
- **curl**
- **Thunder Client (VS Code)**

## Suporte

Para dúvidas ou problemas, consulte a documentação do Laravel ou entre em contato com a equipe de desenvolvimento.
