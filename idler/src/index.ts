import { RabbitMQAdapter } from './adapters/RabbitMQAdapter';
import { IQueueClient } from './interfaces/IQueueClient';
import { ImapIdleWorker } from './workers/imap-idle.worker';
import { EmailProcessorService } from './services/email-processor.service';
import { AccountFetcherService } from './services/account-fetcher.service';
import { AccountManagerService } from './services/account-manager.service';
import { AccountSyncService } from './services/account-sync.service';
import { QueueMonitorService } from './services/queue-monitor.service';
import { AccountConfig } from './types/account.types';
import { AxiosAdapter } from './adapters/AxiosAdapter';
import { MailParserAdapter } from './adapters/MailParserAdapter';
import { NodeImapAdapter } from './adapters/NodeImapAdapter';
import dotenv from 'dotenv';

dotenv.config();

const RABBITMQ_URL = process.env.RABBITMQ_URL || 'amqp://guest:guest@rabbitmq:5672';
const PHP_API_URL = process.env.PHP_API_URL || 'http://localhost:8085/api';
const INTERNAL_API_TOKEN = process.env.INTERNAL_API_TOKEN || '';

let queueClient: IQueueClient;
let processor: EmailProcessorService;
let accountManager: AccountManagerService;
let syncService: AccountSyncService;
let monitor: QueueMonitorService;

async function main(): Promise<void> {
  console.log('Starting IDLE Worker...');

  queueClient = await RabbitMQAdapter.create(RABBITMQ_URL);
  const httpClient = new AxiosAdapter(PHP_API_URL, { token: INTERNAL_API_TOKEN });
  const rabbitManagementClient = new AxiosAdapter('http://rabbitmq:15672', { username: 'guest', password: 'guest' });
  const emailParser = new MailParserAdapter();

  const accountFetcher = new AccountFetcherService(httpClient);
  processor = new EmailProcessorService(queueClient, httpClient, emailParser);
  monitor = new QueueMonitorService(rabbitManagementClient);

  const workerFactory = (account: AccountConfig) => {
    const imapClient = new NodeImapAdapter({
      user: account.username || account.email,
      password: account.password,
      host: account.host,
      port: account.port,
      tls: account.secure,
      tlsOptions: { rejectUnauthorized: false },
      connTimeout: 60000,
      authTimeout: 30000,
      keepalive: {
        interval: 10000,
        idleInterval: 300000,
        forceNoop: true,
      },
    });
    return new ImapIdleWorker(account, queueClient, imapClient);
  };

  accountManager = new AccountManagerService(accountFetcher, workerFactory);
  syncService = new AccountSyncService(queueClient, accountManager);

  await processor.startProcessing();
  await syncService.startListening();
  await accountManager.fetchAndStartWorkers();

  setInterval(async () => {
    console.log('Security Polling: Updating account list...');
    await accountManager.fetchAndStartWorkers();
  }, 600000); 

  setInterval(async () => {
    await monitor.checkQueueSize();
  }, 60000);

  process.on('SIGTERM', shutdown);
  process.on('SIGINT', shutdown);
}

async function shutdown(): Promise<void> {
  if (processor) processor.stopProcessing();
  if (accountManager) await accountManager.shutdown();
  if (queueClient) await (queueClient as RabbitMQAdapter).close();
  console.log('Shutdown complete');
  process.exit(0);
}

main().catch((error) => {
  console.error('Fatal error:', error);
  process.exit(1);
});
