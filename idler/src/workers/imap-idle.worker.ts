import Imap from 'imap';
import { EventEmitter } from 'events';
import { Redis } from 'ioredis';
import { AccountConfig } from '../types/account.types';

export class ImapIdleWorker extends EventEmitter {
  private imap: Imap | null = null;
  private redis: Redis;
  private account: AccountConfig;
  private reconnectTimeout: NodeJS.Timeout | null = null;
  private isIdle: boolean = false;
  private reconnectAttempts: number = 0;
  private readonly maxReconnectAttempts: number = 10;

  constructor(account: AccountConfig, redis: Redis) {
    super();
    this.account = account;
    this.redis = redis;
  }

  async connect(): Promise<void> {
    return new Promise((resolve, reject) => {
      if (this.imap) {
        this.disconnect();
      }

      console.log(`🔌 Conectando: ${this.account.email}...`);

      this.imap = new Imap({
        user: this.account.username || this.account.email,
        password: this.account.password,
        host: this.account.host,
        port: this.account.port,
        tls: this.account.secure,
        tlsOptions: { rejectUnauthorized: false },
        connTimeout: 60000,
        authTimeout: 30000,
        keepalive: {
          interval: 10000, // Keepalive a cada 10 segundos
          idleInterval: 300000, // IDLE timeout de 5 minutos
          forceNoop: true,
        },
      });

      this.imap.once('ready', () => {
        console.log(`✅ Conectado: ${this.account.email}`);
        this.reconnectAttempts = 0;
        this.openInbox();
        resolve();
      });

      this.imap.once('error', (err: Error) => {
        console.error(`❌ Erro IMAP [${this.account.email}]:`, err.message);
        this.scheduleReconnect();
        reject(err);
      });

      this.imap.once('end', () => {
        console.log(`🔌 Conexão encerrada: ${this.account.email}`);
        if (!this.reconnectTimeout) {
          this.scheduleReconnect();
        }
      });

      this.imap.on('expunge', (seqno: number) => {
        console.log(`🗑️ Email expunged [${this.account.email}]:`, seqno);
      });

      this.imap.connect();
    });
  }

  private openInbox(): void {
    if (!this.imap) return;

    this.imap.openBox('INBOX', false, (err, box) => {
      if (err) {
        console.error(`❌ Erro ao abrir INBOX [${this.account.email}]:`, err);
        this.scheduleReconnect();
        return;
      }

      console.log(`📬 INBOX aberto [${this.account.email}]: ${box.messages.total} mensagens`);
      this.startIdle();
    });
  }

  private startIdle(): void {
    if (!this.imap || this.isIdle) return;

    // Escuta evento 'mail' quando servidor notifica email novo
    this.imap.on('mail', async (numNewMsgs: number) => {
      console.log(`📧 ${numNewMsgs} novo(s) email(s) em ${this.account.email}`);
      await this.fetchNewEmails();
    });

    // Inicia IDLE
    (this.imap as any).idle((err: Error | null) => {
      if (err) {
        console.error(`❌ Erro no IDLE [${this.account.email}]:`, err.message);
        this.isIdle = false;
        this.scheduleReconnect();
      } else {
        this.isIdle = true;
        console.log(`👂 IDLE ativo [${this.account.email}]`);
      }
    });
  }

  private async fetchNewEmails(): Promise<void> {
    if (!this.imap) return;

    // Para o IDLE temporariamente para buscar emails
    if (this.isIdle && this.imap) {
      (this.imap as any).idle_stop();
      this.isIdle = false;
    }

    this.imap.search(['UNSEEN'], async (err, results) => {
      if (err) {
        console.error(`❌ Erro na busca [${this.account.email}]:`, err);
        this.startIdle();
        return;
      }

      if (!results || results.length === 0) {
        console.log(`ℹ️ Nenhum email novo [${this.account.email}]`);
        this.startIdle();
        return;
      }

      console.log(`📥 Buscando ${results.length} email(s) novo(s) [${this.account.email}]`);

      if (!this.imap) return;

      const fetch = this.imap.fetch(results, {
        bodies: '',
        struct: true,
      });

      let processedCount = 0;

      fetch.on('message', (msg) => {
        let buffer = '';
        let uid: number | null = null;

        msg.on('body', (stream) => {
          stream.on('data', (chunk) => {
            buffer += chunk.toString('utf8');
          });
        });

        msg.once('attributes', (attrs) => {
          uid = attrs.uid;
        });

        msg.once('end', async () => {
          try {
            // Adiciona à fila Redis
            await this.redis.lpush(
              'email:processing:queue',
              JSON.stringify({
                account_id: this.account.id,
                account_email: this.account.email,
                raw_email: buffer,
                received_at: new Date().toISOString(),
                uid: uid,
              })
            );

            processedCount++;
            console.log(`✅ Email ${uid} adicionado à fila [${this.account.email}]`);
          } catch (error: any) {
            console.error(`❌ Erro ao adicionar à fila [${this.account.email}]:`, error.message);
          }
        });
      });

      fetch.once('end', () => {
        console.log(`✅ ${processedCount} email(s) processado(s) [${this.account.email}]`);

        // Marca como lido e reinicia IDLE
        if (results.length > 0 && this.imap) {
          this.imap.setFlags(results, ['\\Seen'], (err) => {
            if (err) {
              console.error(`⚠️ Erro ao marcar como lido [${this.account.email}]:`, err);
            }
            this.startIdle();
          });
        } else {
          this.startIdle();
        }
      });

      fetch.once('error', (err) => {
        console.error(`❌ Erro no fetch [${this.account.email}]:`, err);
        this.startIdle();
      });
    });
  }

  private scheduleReconnect(): void {
    if (this.reconnectTimeout) return;

    if (this.reconnectAttempts >= this.maxReconnectAttempts) {
      console.error(`❌ Máximo de tentativas atingido [${this.account.email}]`);
      this.emit('maxReconnectAttempts');
      return;
    }

    const delay = Math.min(5000 * (this.reconnectAttempts + 1), 60000); // Max 60s
    this.reconnectAttempts++;

    console.log(
      `🔄 Reconectando em ${delay / 1000}s [${this.account.email}] (tentativa ${this.reconnectAttempts}/${this.maxReconnectAttempts})`
    );

    this.reconnectTimeout = setTimeout(async () => {
      this.reconnectTimeout = null;
      try {
        await this.connect();
      } catch (err: any) {
        console.error(`❌ Falha na reconexão [${this.account.email}]:`, err.message);
      }
    }, delay);
  }

  async disconnect(): Promise<void> {
    if (this.reconnectTimeout) {
      clearTimeout(this.reconnectTimeout);
      this.reconnectTimeout = null;
    }

    if (this.imap) {
      if (this.isIdle) {
        try {
          (this.imap as any).idle_stop();
        } catch (err) {
          // Ignora erros ao parar IDLE
        }
        this.isIdle = false;
      }

      this.imap.end();
      this.imap = null;
    }
  }

  getAccountEmail(): string {
    return this.account.email;
  }

  isConnected(): boolean {
    return this.imap !== null && this.isIdle;
  }
}

