# 🚀 Como Rodar e Testar o IDLE Worker

## Passo 1: Verificar Pré-requisitos

### 1.1 Verificar se Docker está rodando
```bash
docker ps
```

### 1.2 Verificar se Laravel está acessível
```bash
# Tente ambas as portas
curl http://localhost:8080/api/ping
curl http://localhost:8085/api/ping
```

## Passo 2: Configurar Variáveis de Ambiente

### 2.1 Criar/Editar `docker/.env.docker`

Crie o arquivo `docker/.env.docker` se não existir:

```env
# Token do cliente (obtenha do banco)
CLIENT_TOKEN=seu_token_aqui

# Token interno (opcional)
INTERNAL_API_TOKEN=

# Configurações do Laravel (se necessário)
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=staging
DB_USERNAME=user
DB_PASSWORD=123
```

### 2.2 Obter Token do Cliente

Execute no PostgreSQL:
```sql
SELECT token FROM clients LIMIT 1;
```

Ou via Docker:
```bash
docker exec -it smail_postgres_db psql -U user -d staging -c "SELECT token FROM clients LIMIT 1;"
```

## Passo 3: Verificar Contas no Banco

### 3.1 Verificar se há contas ativas

```bash
docker exec -it smail_postgres_db psql -U user -d staging -c "SELECT id, email_address, host, port, active FROM accounts WHERE active = true AND password IS NOT NULL LIMIT 5;"
```

### 3.2 Testar endpoint de contas

```bash
# Ajuste a porta conforme necessário (8080 ou 8085)
curl http://localhost:8080/api/accounts/active
```

## Passo 4: Subir Serviços

### 4.1 Subir Redis e Worker

```bash
# Subir apenas Redis e Worker (se app já estiver rodando)
docker-compose up -d redis idler-worker

# OU subir tudo
docker-compose up -d
```

### 4.2 Verificar status

```bash
docker-compose ps
```

Você deve ver:
- ✅ `smail_redis` - Up
- ✅ `smail_idler_worker` - Up

## Passo 5: Monitorar Logs

### 5.1 Logs do Worker (em tempo real)

```bash
docker-compose logs -f idler-worker
```

**O que você deve ver:**
```
✅ Conectado ao Redis
📋 Buscando contas ativas...
✅ 2 conta(s) encontrada(s)
🔌 Conectando: email1@exemplo.com...
✅ Conectado: email1@exemplo.com
📬 INBOX aberto [email1@exemplo.com]: 150 mensagens
👂 IDLE ativo [email1@exemplo.com]
```

### 5.2 Se houver erros

**Erro de conexão Redis:**
```
❌ Erro no Redis: connect ECONNREFUSED
```
→ Verifique se Redis está rodando: `docker-compose ps redis`

**Erro ao buscar contas:**
```
❌ Erro ao buscar contas: Request failed
```
→ Verifique se a API está acessível e a porta está correta

**Erro de autenticação IMAP:**
```
❌ Erro IMAP: Invalid credentials
```
→ Verifique senha e credenciais da conta

## Passo 6: Testar Recebimento de Email

### 6.1 Enviar email de teste

Envie um email para uma das contas monitoradas.

### 6.2 Verificar logs

Você deve ver nos logs:
```
📧 1 novo(s) email(s) em email@exemplo.com
📥 Buscando 1 email(s) novo(s)
✅ Email 123 adicionado à fila
📨 Processando email de email@exemplo.com...
✅ Email salvo: "Assunto" [email@exemplo.com] (500ms)
```

### 6.3 Verificar no banco

```bash
docker exec -it smail_postgres_db psql -U user -d staging -c "SELECT id, \"from\", subject, created_at FROM emails ORDER BY created_at DESC LIMIT 5;"
```

## Passo 7: Monitorar Fila Redis

### 7.1 Verificar tamanho da fila

```bash
docker exec smail_redis redis-cli LLEN email:processing:queue
```

### 7.2 Ver emails na fila

```bash
docker exec smail_redis redis-cli LRANGE email:processing:queue 0 5
```

## Comandos Rápidos

```bash
# Ver logs do worker
docker-compose logs -f idler-worker

# Reiniciar worker
docker-compose restart idler-worker

# Rebuild worker (após mudanças)
docker-compose build idler-worker
docker-compose up -d idler-worker

# Parar worker
docker-compose stop idler-worker

# Ver status de todos os serviços
docker-compose ps

# Ver logs de todos os serviços
docker-compose logs -f
```

## Troubleshooting Rápido

| Problema | Solução |
|----------|---------|
| Worker não inicia | Verifique logs: `docker-compose logs idler-worker` |
| Redis não conecta | Suba Redis: `docker-compose up -d redis` |
| Nenhuma conta encontrada | Verifique banco: contas com `active=true` e `password` |
| Erro de autenticação | Verifique `CLIENT_TOKEN` no `.env.docker` |
| Emails não processam | Verifique logs do processador e fila Redis |

## Próximos Passos

1. ✅ Worker rodando
2. ✅ Contas conectadas
3. ✅ IDLE ativo
4. 📧 Enviar email de teste
5. ✅ Verificar se foi salvo no banco

