import { Redis } from 'ioredis';
import axios, { AxiosInstance } from 'axios';
import { simpleParser, ParsedMail } from 'mailparser';
import { EmailQueueItem } from '../types/account.types';

export class EmailProcessorService {
  private redis: Redis;
  private apiClient: AxiosInstance;
  private isProcessing: boolean = false;

  constructor(redis: Redis, phpApiUrl: string) {
    this.redis = redis;
    this.apiClient = axios.create({
      baseURL: phpApiUrl,
      timeout: 60000,
      headers: {
        'Content-Type': 'application/json',
      },
    });
  }

  async startProcessing(): Promise<void> {
    if (this.isProcessing) {
      console.warn('⚠️ Processador já está rodando');
      return;
    }

    this.isProcessing = true;
    console.log('🔄 Iniciando processador de emails...');

    while (this.isProcessing) {
      try {
        // BRPOP = Blocking Right Pop
        // Espera até ter algo na fila (timeout 5s)
        const emailData = await this.redis.brpop('email:processing:queue', 5);

        if (!emailData) continue; // Timeout, tenta de novo

        const email: EmailQueueItem = JSON.parse(emailData[1]);
        await this.processEmail(email);
      } catch (error: any) {
        console.error('❌ Erro ao processar email da fila:', error.message);
        // Aguarda um pouco antes de tentar de novo
        await new Promise((resolve) => setTimeout(resolve, 1000));
      }
    }
  }

  stopProcessing(): void {
    this.isProcessing = false;
    console.log('🛑 Parando processador de emails...');
  }

  private async processEmail(emailData: EmailQueueItem): Promise<void> {
    const startTime = Date.now();
    let parsed: ParsedMail | null = null;

    try {
      console.log(`📨 Processando email de ${emailData.account_email}...`);

      // Parse do email raw
      parsed = await simpleParser(emailData.raw_email);

      // Validações básicas
      if (!parsed || !parsed.from) {
        console.warn(`⚠️ Email sem remetente, ignorando...`);
        return;
      }

      // A partir daqui, parsed não é null
      const email = parsed;

      // Extrai anexos
      const attachments = [];
      if (email.attachments && email.attachments.length > 0) {
        for (const att of email.attachments) {
          attachments.push({
            filename: att.filename || 'anexo',
            mime_type: att.contentType || 'application/octet-stream',
            size: att.size || 0,
            content: att.content.toString('base64'),
          });
        }
      }

      // Prepara dados para API PHP
      const payload = {
        email_account: emailData.account_email,
        from: this.extractFromAddress(email),
        to: this.extractAddresses(email.to),
        cc: this.extractAddresses(email.cc),
        bcc: this.extractAddresses(email.bcc),
        subject: email.subject || '(Sem assunto)',
        body: email.html || email.text || '',
        thread_id: this.extractThreadId(email),
        reply_to: email.replyTo ? this.extractAddresses(email.replyTo)[0] || null : null,
        external_id: email.messageId ? email.messageId.replace(/[<>]/g, '') : null,
        processed_at: email.date ? email.date.toISOString().replace('T', ' ').substring(0, 19) : null,
        attachments: attachments.length > 0 ? attachments : undefined,
        complements: null,
      };

      // Chama API PHP (SaveEmailFromIdleController) - rota interna sem autenticação
      const response = await this.apiClient.post('/internal/save-email', payload);

      const duration = Date.now() - startTime;
      console.log(
        `✅ Email salvo: "${email.subject || '(Sem assunto)'}" [${emailData.account_email}] (${duration}ms)`
      );
    } catch (error: any) {
      const duration = Date.now() - startTime;
      console.error(`❌ Erro ao processar email [${emailData.account_email}]:`, error.message);

      if (error.response) {
        console.error('Status:', error.response.status);
        console.error('Data:', error.response.data);
      }

      // Pode adicionar à fila de erro para retry posterior
      // await this.redis.lpush('email:error:queue', JSON.stringify(emailData));
    }
  }

  private extractFromAddress(parsed: ParsedMail): string {
    if (parsed.from?.value && parsed.from.value.length > 0) {
      return parsed.from.value[0].address || 'unknown@unknown.com';
    }
    if (parsed.from?.text) {
      // Tenta extrair email do texto
      const match = parsed.from.text.match(/<(.+?)>/);
      if (match) return match[1];
      return parsed.from.text;
    }
    return 'unknown@unknown.com';
  }

  private extractAddresses(address: any): string[] {
    if (!address) return [];

    if (Array.isArray(address)) {
      return address
        .map((addr) => {
          if (typeof addr === 'string') return addr;
          if (addr.address) return addr.address;
          if (addr.mailbox && addr.host) return `${addr.mailbox}@${addr.host}`;
          return null;
        })
        .filter((addr): addr is string => addr !== null);
    }

    if (address.value && Array.isArray(address.value)) {
      return address.value.map((v: any) => v.address || `${v.mailbox}@${v.host}`);
    }

    if (address.text) {
      // Tenta extrair emails do texto
      const emails: string[] = [];
      const regex = /([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z0-9_-]+)/g;
      const matches = address.text.match(regex);
      if (matches) emails.push(...matches);
      return emails;
    }

    return [];
  }

  private extractThreadId(parsed: ParsedMail): string | null {
    // Lógica similar ao ReceiveEmailsCron.php
    // Primeiro tenta References
    if (parsed.references && parsed.references.length > 0) {
      for (const ref of parsed.references) {
        const match = ref.match(/<([^@]+)@superestagios\.com\.br>/);
        if (match) return match[1];
      }
    }

    // Depois tenta In-Reply-To
    if (parsed.inReplyTo) {
      const inReplyTo = Array.isArray(parsed.inReplyTo) ? parsed.inReplyTo[0] : parsed.inReplyTo;
      const match = inReplyTo.match(/<([^@]+)@/);
      if (match) return match[1];
    }

    // Por último, usa Message-ID
    if (parsed.messageId) {
      const match = parsed.messageId.match(/<([^@]+)@/);
      if (match) return match[1];
      return parsed.messageId.replace(/[<>]/g, '');
    }

    return null;
  }
}

