import { IQueueClient } from '../interfaces/IQueueClient';
import { AccountManagerService } from './account-manager.service';

export class AccountSyncService {
  private queueClient: IQueueClient;
  private accountManager: AccountManagerService;

  constructor(queueClient: IQueueClient, accountManager: AccountManagerService) {
    this.queueClient = queueClient;
    this.accountManager = accountManager;
  }

  async startListening(): Promise<void> {
    await this.queueClient.consume('account_sync_queue', async (msg: any) => {
      try {
        const data = JSON.parse(msg.content.toString());
        if (data.action === 'account_created') {
          console.log(`🔔 Evento de nova conta: ${data.account_id}`);
          await this.accountManager.fetchAndStartWorkers();
        }
        this.queueClient.ack(msg);
      } catch (error: any) {
        console.error('❌ Erro no sync de contas:', error.message);
        this.queueClient.nack(msg, true);
      }
    });
  }
}
