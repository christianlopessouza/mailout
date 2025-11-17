import { Redis } from 'ioredis';
import { ImapIdleWorker } from './workers/imap-idle.worker';
import { EmailProcessorService } from './services/email-processor.service';
import { AccountFetcherService } from './services/account-fetcher.service';
import { AccountConfig } from './types/account.types';
import dotenv from 'dotenv';

// Carrega variáveis de ambiente
dotenv.config();

const REDIS_HOST = process.env.REDIS_HOST || 'localhost';
const REDIS_PORT = parseInt(process.env.REDIS_PORT || '6379');
const PHP_API_URL = process.env.PHP_API_URL || 'http://localhost:8085/api';
const INTERNAL_API_TOKEN = process.env.INTERNAL_API_TOKEN || '';
const ACCOUNT_REFRESH_INTERVAL = parseInt(process.env.ACCOUNT_REFRESH_INTERVAL || '3600000'); // 1 hora

// Cria conexão Redis
const redis = new Redis({
  host: REDIS_HOST,
  port: REDIS_PORT,
  retryStrategy: (times) => {
    const delay = Math.min(times * 50, 2000);
    console.log(`🔄 Tentando reconectar Redis (tentativa ${times})...`);
    return delay;
  },
  maxRetriesPerRequest: 3,
});

redis.on('connect', () => {
  console.log('✅ Conectado ao Redis');
});

redis.on('error', (err) => {
  console.error('❌ Erro no Redis:', err.message);
});

// Serviços
const accountFetcher = new AccountFetcherService(PHP_API_URL, INTERNAL_API_TOKEN);
const processor = new EmailProcessorService(redis, PHP_API_URL);

// Workers ativos
const workers: Map<string, ImapIdleWorker> = new Map();

async function fetchAndStartWorkers(): Promise<void> {
  console.log('📋 Buscando contas ativas...');
  const accounts = await accountFetcher.fetchActiveAccounts();

  if (accounts.length === 0) {
    console.warn('⚠️ Nenhuma conta ativa encontrada');
    return;
  }

  // Remove workers de contas que não estão mais ativas
  const activeAccountIds = new Set(accounts.map((acc) => acc.id));
  for (const [accountId, worker] of workers.entries()) {
    if (!activeAccountIds.has(accountId)) {
      console.log(`🛑 Removendo worker para conta ${accountId}`);
      await worker.disconnect();
      workers.delete(accountId);
    }
  }

  // Cria/atualiza workers para contas ativas
  for (const account of accounts) {
    if (workers.has(account.id)) {
      // Worker já existe, verifica se está conectado
      const worker = workers.get(account.id)!;
      if (!worker.isConnected()) {
        console.log(`🔄 Reconectando worker para ${account.email}...`);
        try {
          await worker.connect();
        } catch (error: any) {
          console.error(`❌ Erro ao reconectar ${account.email}:`, error.message);
        }
      }
    } else {
      // Cria novo worker
      console.log(`📧 Criando worker para: ${account.email}`);
      const worker = new ImapIdleWorker(account, redis);

      worker.on('maxReconnectAttempts', () => {
        console.error(`❌ Máximo de tentativas atingido para ${account.email}`);
        workers.delete(account.id);
      });

      try {
        await worker.connect();
        workers.set(account.id, worker);
        console.log(`✅ Worker ativo para: ${account.email}`);
      } catch (error: any) {
        console.error(`❌ Falha ao conectar ${account.email}:`, error.message);
      }
    }
  }

  console.log(`🎯 Total de ${workers.size} worker(s) ativo(s)`);
}

async function main(): Promise<void> {
  console.log('🚀 Iniciando IDLE Worker...');
  console.log(`📡 Redis: ${REDIS_HOST}:${REDIS_PORT}`);
  console.log(`🌐 API PHP: ${PHP_API_URL}`);

  // Inicia processador de emails (consome fila Redis)
  processor.startProcessing().catch((error) => {
    console.error('❌ Erro fatal no processador:', error);
    process.exit(1);
  });

  // Busca contas e inicia workers
  await fetchAndStartWorkers();

  // Agenda atualização periódica de contas
  setInterval(async () => {
    console.log('🔄 Atualizando lista de contas...');
    await fetchAndStartWorkers();
  }, ACCOUNT_REFRESH_INTERVAL);

  // Graceful shutdown
  process.on('SIGTERM', async () => {
    console.log('🛑 Recebido SIGTERM, encerrando workers...');
    await shutdown();
  });

  process.on('SIGINT', async () => {
    console.log('🛑 Recebido SIGINT, encerrando workers...');
    await shutdown();
  });
}

async function shutdown(): Promise<void> {
  processor.stopProcessing();

  console.log('🔌 Desconectando workers...');
  const disconnectPromises = Array.from(workers.values()).map((worker) => worker.disconnect());
  await Promise.all(disconnectPromises);

  console.log('🔌 Desconectando Redis...');
  redis.disconnect();

  console.log('✅ Shutdown completo');
  process.exit(0);
}

// Tratamento de erros não capturados
process.on('uncaughtException', (error) => {
  console.error('❌ Erro não capturado:', error);
  shutdown().then(() => process.exit(1));
});

process.on('unhandledRejection', (reason, promise) => {
  console.error('❌ Promise rejeitada não tratada:', reason);
  console.error('Promise:', promise);
});

// Inicia aplicação
main().catch((error) => {
  console.error('❌ Erro fatal:', error);
  process.exit(1);
});

