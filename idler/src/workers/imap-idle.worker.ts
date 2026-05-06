import { IImapClient } from '../interfaces/IImapClient';
import { EventEmitter } from 'events';
import { IQueueClient } from '../interfaces/IQueueClient';
import { AccountConfig } from '../types/account.types';

export class ImapIdleWorker extends EventEmitter {
  private imapClient: IImapClient;
  private queueClient: IQueueClient;
  private account: AccountConfig;
  private reconnectTimeout: NodeJS.Timeout | null = null;
  private isIdle: boolean = false;
  private reconnectAttempts: number = 0;
  private readonly maxReconnectAttempts: number = 10;
  private readonly QUEUE_NAME = 'email_processing_queue';

  constructor(
    account: AccountConfig, 
    queueClient: IQueueClient, 
    imapClient: IImapClient
  ) {
    super();
    this.account = account;
    this.queueClient = queueClient;
    this.imapClient = imapClient;

    this.imapClient.on('ready', () => {
      this.reconnectAttempts = 0;
      this.openInbox();
    });
    this.imapClient.on('error', (err) => {
      console.error(`IMAP error [${this.account.email}]:`, err.message);
      this.scheduleReconnect();
    });
    this.imapClient.on('mail', async (num) => {
      console.log(`${num} new email(s) in ${this.account.email}`);
      await this.fetchNewEmails();
    });
  }

  async connect(): Promise<void> {
    console.log(`Connecting: ${this.account.email}...`);
    await this.imapClient.connect();
  }

  private openInbox(): void {
    console.log(`INBOX opened [${this.account.email}]`);
    this.startIdle();
  }

  private async startIdle(): Promise<void> {
    if (this.isIdle) return;
    try {
      await this.imapClient.idle();
      this.isIdle = true;
      console.log(`IDLE active [${this.account.email}]`);
    } catch (err: any) {
      console.error(`Error in IDLE [${this.account.email}]:`, err.message);
      this.isIdle = false;
      this.scheduleReconnect();
    }
  }

  private async fetchNewEmails(): Promise<void> {
    if (this.isIdle) {
      this.imapClient.idleStop();
      this.isIdle = false;
    }

    const results = await this.imapClient.search(['UNSEEN']);
    if (!results || results.length === 0) {
      console.log(`No new emails [${this.account.email}]`);
      this.startIdle();
      return;
    }

    console.log(`Fetching ${results.length} new email(s) [${this.account.email}]`);

    const fetch = this.imapClient.fetch(results, { bodies: '', struct: true });
    
    let processedCount = 0;

    fetch.on('message', (msg: any) => {
      let buffer = '';
      let uid: number | null = null;

      msg.on('body', (stream: any) => {
        stream.on('data', (chunk: any) => {
          buffer += chunk.toString('utf8');
        });
      });

      msg.once('attributes', (attrs: any) => {
        uid = attrs.uid;
      });

      msg.once('end', async () => {
        try {
          await this.queueClient.push(
            this.QUEUE_NAME,
            {
              account_id: this.account.id,
              account_email: this.account.email,
              raw_email: buffer,
              received_at: new Date().toISOString(),
              uid: uid,
            }
          );

          processedCount++;
          console.log(`Email ${uid} added to queue [${this.account.email}]`);
        } catch (error: any) {
          console.error(`Error adding to queue [${this.account.email}]:`, error.message);
        }
      });
    });

    fetch.once('end', () => {
      console.log(`${processedCount} email(s) processed [${this.account.email}]`);

      if (results.length > 0) {
        this.imapClient.setFlags(results, ['\\Seen']).catch((err: any) => {
          console.error(`Error marking as read [${this.account.email}]:`, err);
        }).finally(() => {
          this.startIdle();
        });
      } else {
        this.startIdle();
      }
    });

    fetch.once('error', (err: any) => {
      console.error(`Error in fetch [${this.account.email}]:`, err);
      this.startIdle();
    });
  }

  private scheduleReconnect(): void {
    if (this.reconnectTimeout) return;

    if (this.reconnectAttempts >= this.maxReconnectAttempts) {
      console.error(`Maximum reconnection attempts reached [${this.account.email}]`);
      this.emit('maxReconnectAttempts');
      return;
    }

    const delay = Math.min(5000 * (this.reconnectAttempts + 1), 60000);
    this.reconnectAttempts++;

    console.log(
      `Reconnecting in ${delay / 1000}s [${this.account.email}] (attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts})`
    );

    this.reconnectTimeout = setTimeout(async () => {
      this.reconnectTimeout = null;
      try {
        await this.connect();
      } catch (err: any) {
        console.error(`Reconnection failed [${this.account.email}]:`, err.message);
      }
    }, delay);
  }

  async disconnect(): Promise<void> {
    if (this.reconnectTimeout) {
      clearTimeout(this.reconnectTimeout);
      this.reconnectTimeout = null;
    }
    this.imapClient.disconnect();
  }

  getAccountEmail(): string {
    return this.account.email;
  }

  isConnected(): boolean {
    return this.isIdle;
  }
}
