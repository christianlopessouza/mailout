# Guia de Teste do IDLE Worker

## Pré-requisitos

1. Docker e Docker Compose instalados
2. Contas de email cadastradas no banco com:
   - `active = true`
   - `password` preenchido
   - `host` e `port` configurados

## Passo 1: Verificar Configuração

### 1.1 Criar arquivo .env.docker (se não existir)

Crie o arquivo `docker/.env.docker` com as variáveis necessárias:

```env
# Token do cliente (obtenha de um cliente no banco)
CLIENT_TOKEN=seu_token_do_cliente_aqui

# Token interno opcional (pode deixar vazio se não usar autenticação)
INTERNAL_API_TOKEN=

# Outras variáveis do Laravel...
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=staging
DB_USERNAME=user
DB_PASSWORD=123
```

### 1.2 Obter token do cliente

Execute no banco ou via API:

```sql
SELECT token FROM clients LIMIT 1;
```

Ou via API (se já tiver um cliente configurado):
```bash
curl http://localhost:8085/api/ping
```

## Passo 2: Verificar Contas no Banco

### 2.1 Verificar se há contas ativas

```sql
SELECT id, email_address, host, port, active 
FROM accounts 
WHERE active = true 
  AND password IS NOT NULL 
  AND password != '';
```

### 2.2 Testar endpoint de contas

```bash
curl http://localhost:8085/api/accounts/active
```

Deve retornar um array JSON com as contas.

## Passo 3: Subir Serviços

### 3.1 Subir todos os serviços

```bash
docker-compose up -d
```

Isso vai subir:
- `app` (Laravel)
- `postgres` (Banco de dados)
- `redis` (Fila)
- `idler-worker` (Worker IDLE)

### 3.2 Verificar status

```bash
docker-compose ps
```

Todos os serviços devem estar com status "Up".

## Passo 4: Monitorar Logs

### 4.1 Logs do worker

```bash
docker-compose logs -f idler-worker
```

Você deve ver:
- `✅ Conectado ao Redis`
- `📋 Buscando contas ativas...`
- `✅ X conta(s) encontrada(s)`
- `🔌 Conectando: email@exemplo.com...`
- `✅ Conectado: email@exemplo.com`
- `📬 INBOX aberto`
- `👂 IDLE ativo`

### 4.2 Logs do Redis

```bash
docker-compose logs -f redis
```

### 4.3 Logs do app (Laravel)

```bash
docker-compose logs -f app
```

## Passo 5: Testar Recebimento de Email

### 5.1 Enviar email de teste

Envie um email para uma das contas monitoradas.

### 5.2 Verificar logs do worker

Você deve ver:
```
📧 1 novo(s) email(s) em email@exemplo.com
📥 Buscando 1 email(s) novo(s)
✅ Email 123 adicionado à fila
✅ 1 email(s) processado(s)
```

### 5.3 Verificar processamento

Nos logs do worker, você deve ver:
```
📨 Processando email de email@exemplo.com...
✅ Email salvo: "Assunto do email" (500ms)
```

### 5.4 Verificar no banco

```sql
SELECT id, from, subject, created_at 
FROM emails 
ORDER BY created_at DESC 
LIMIT 5;
```

O email deve aparecer na lista.

## Passo 6: Monitorar Fila Redis

### 6.1 Conectar ao Redis

```bash
docker exec -it smail_redis redis-cli
```

### 6.2 Verificar tamanho da fila

```redis
LLEN email:processing:queue
```

### 6.3 Ver emails na fila (sem remover)

```redis
LRANGE email:processing:queue 0 10
```

### 6.4 Ver primeiro email (e remover)

```redis
RPOP email:processing:queue
```

## Troubleshooting

### Worker não conecta

1. Verificar se Redis está rodando:
   ```bash
   docker-compose ps redis
   ```

2. Verificar se API está acessível:
   ```bash
   curl http://localhost:8085/api/ping
   ```

3. Verificar logs:
   ```bash
   docker-compose logs idler-worker
   ```

### Nenhuma conta encontrada

1. Verificar se há contas no banco:
   ```sql
   SELECT COUNT(*) FROM accounts WHERE active = true AND password IS NOT NULL;
   ```

2. Testar endpoint:
   ```bash
   curl http://localhost:8085/api/accounts/active
   ```

### Emails não estão sendo processados

1. Verificar se há emails na fila:
   ```bash
   docker exec smail_redis redis-cli LLEN email:processing:queue
   ```

2. Verificar logs do processador no worker

3. Verificar se `CLIENT_TOKEN` está correto

4. Verificar logs do Laravel para erros na API

### Erro de autenticação

1. Verificar se `CLIENT_TOKEN` está correto
2. Verificar se o token existe no banco:
   ```sql
   SELECT token FROM clients WHERE token = 'seu_token';
   ```

### Worker reconecta constantemente

1. Verificar credenciais IMAP
2. Verificar conectividade de rede
3. Verificar se servidor IMAP suporta IDLE
4. Verificar logs para erros específicos

## Comandos Úteis

```bash
# Reiniciar apenas o worker
docker-compose restart idler-worker

# Rebuild do worker (após mudanças no código)
docker-compose build idler-worker
docker-compose up -d idler-worker

# Ver logs em tempo real
docker-compose logs -f idler-worker

# Parar tudo
docker-compose down

# Parar e remover volumes
docker-compose down -v
```

## Teste Rápido

Execute o script de teste:

```bash
chmod +x idler/test-setup.sh
./idler/test-setup.sh
```

