# GSmail IDLE Worker

Worker Node.js/TypeScript para monitorar emails via IMAP IDLE.

## Como funciona

1. **Inicialização**: Busca contas ativas do banco via API PHP
2. **Conexão IDLE**: Cria uma conexão IMAP IDLE para cada conta
3. **Notificação**: Quando chega email novo, servidor IMAP notifica imediatamente
4. **Processamento**: Email é adicionado à fila Redis
5. **Salvamento**: Processador consome fila e chama API PHP para salvar

## Variáveis de Ambiente

```env
REDIS_HOST=redis
REDIS_PORT=6379
PHP_API_URL=http://app:80/api
CLIENT_TOKEN=token_do_cliente
INTERNAL_API_TOKEN=token_interno_opcional
ACCOUNT_REFRESH_INTERVAL=3600000
```

## Desenvolvimento

```bash
cd idler
npm install
npm run dev
```

## Build

```bash
npm run build
npm start
```

## Docker

O worker é construído automaticamente pelo docker-compose:

```bash
docker-compose up idler-worker
```

