# Configuração do IDLE Worker

## Visão Geral

O IDLE Worker é uma aplicação Node.js/TypeScript que monitora emails em tempo real usando IMAP IDLE, substituindo o polling tradicional.

## Estrutura

```
GSmail/
├── app/                    # Laravel PHP (existente)
├── idler/                  # Worker Node.js/TypeScript
│   ├── src/
│   │   ├── workers/        # Workers IDLE por conta
│   │   ├── services/       # Serviços (processador, account fetcher)
│   │   ├── types/          # Tipos TypeScript
│   │   └── index.ts        # Entry point
│   ├── package.json
│   ├── tsconfig.json
│   └── Dockerfile
└── docker-compose.yaml     # Inclui Redis e worker
```

## Como Funciona

1. **Inicialização**: Worker busca contas ativas via API `/api/accounts/active`
2. **Conexão IDLE**: Cria conexão IMAP IDLE persistente para cada conta
3. **Notificação**: Servidor IMAP notifica quando chega email novo
4. **Fila Redis**: Email é adicionado à fila `email:processing:queue`
5. **Processamento**: Processador consome fila e chama API PHP
6. **Salvamento**: `SaveEmailController` salva usando regra de negócio existente

## Configuração

### Variáveis de Ambiente

Adicione ao `docker/.env.docker`:

```env
# Token do cliente para autenticar na API PHP
CLIENT_TOKEN=seu_token_aqui

# Token interno opcional para proteger endpoint de contas
INTERNAL_API_TOKEN=token_interno_opcional
```

O worker usa estas variáveis automaticamente:
- `REDIS_HOST=redis` (padrão)
- `REDIS_PORT=6379` (padrão)
- `PHP_API_URL=http://app:80/api` (padrão)
- `ACCOUNT_REFRESH_INTERVAL=3600000` (1 hora, padrão)

### Endpoint de Contas

O endpoint `/api/accounts/active` retorna:
```json
[
  {
    "id": "uuid",
    "email_address": "teste@gruposuper.com.br",
    "password": "senha",
    "host": "mail.gruposuper.com.br",
    "port": 993,
    "username": "teste@gruposuper.com.br"
  }
]
```

## Execução

### Desenvolvimento Local

```bash
# 1. Instalar dependências
cd idler
npm install

# 2. Compilar TypeScript
npm run build

# 3. Rodar (precisa de Redis e API PHP rodando)
npm start
```

### Docker Compose

```bash
# Subir todos os serviços
docker-compose up -d

# Ver logs do worker
docker-compose logs -f idler-worker

# Reiniciar apenas o worker
docker-compose restart idler-worker
```

## Monitoramento

### Logs

O worker gera logs detalhados:
- `✅` Conexão bem-sucedida
- `📧` Email novo recebido
- `❌` Erros
- `🔄` Reconexões

### Redis

Verificar fila de processamento:
```bash
docker exec -it smail_redis redis-cli
> LLEN email:processing:queue
> LRANGE email:processing:queue 0 10
```

### Status dos Workers

Os workers se reconectam automaticamente em caso de falha. Máximo de 10 tentativas.

## Troubleshooting

### Worker não conecta

1. Verificar se Redis está rodando: `docker-compose ps redis`
2. Verificar se API PHP está acessível: `curl http://localhost:8085/api/ping`
3. Verificar logs: `docker-compose logs idler-worker`

### Emails não estão sendo processados

1. Verificar se há emails na fila Redis
2. Verificar logs do processador
3. Verificar se `CLIENT_TOKEN` está correto
4. Verificar se `SaveEmailController` está funcionando

### Muitas reconexões

1. Verificar conectividade de rede
2. Verificar credenciais IMAP
3. Verificar se servidor IMAP suporta IDLE

## Deploy no ECS

1. **Task Definition**: Incluir 2 containers:
   - Container 1: Laravel PHP (app)
   - Container 2: Node.js IDLE Worker

2. **Redis**: Usar ElastiCache ou serviço gerenciado

3. **Variáveis de Ambiente**: Configurar no ECS Task Definition

4. **Service**: Criar 2 serviços ECS:
   - Serviço 1: App PHP (público)
   - Serviço 2: Worker IDLE (privado)

## Vantagens sobre Polling

- ✅ Notificação instantânea (segundos vs minutos)
- ✅ Menos carga no servidor IMAP
- ✅ Conexão persistente (mais eficiente)
- ✅ Escalável (múltiplos workers)
- ✅ Resiliente (reconexão automática)

