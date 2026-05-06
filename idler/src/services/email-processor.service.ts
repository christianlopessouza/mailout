import { ParsedMail } from 'mailparser';
import { EmailQueueItem } from '../types/account.types';
import { IQueueClient } from '../interfaces/IQueueClient';
import { IHttpClient } from '../interfaces/IHttpClient';
import { IEmailParser } from '../interfaces/IEmailParser';

export class EmailProcessorService {
  private queueClient: IQueueClient;
  private httpClient: IHttpClient;
  private emailParser: IEmailParser;
  private isProcessing: boolean = false;
  private readonly QUEUE_NAME = 'email_processing_queue';

  constructor(
    queueClient: IQueueClient, 
    httpClient: IHttpClient,
    emailParser: IEmailParser
  ) {
    this.queueClient = queueClient;
    this.httpClient = httpClient;
    this.emailParser = emailParser;
  }

  async startProcessing(): Promise<void> {
    if (this.isProcessing) {
      console.warn('Processor already running');
      return;
    }

    this.isProcessing = true;
    console.log('Starting email processor...');

    await this.queueClient.consume(this.QUEUE_NAME, async (msg: any) => {
      try {
        const email: EmailQueueItem = JSON.parse(msg.content.toString());
        await this.processEmail(email);
        
        this.queueClient.ack(msg);
      } catch (error: any) {
        console.error('Error processing email from queue:', error.message);
        
        this.queueClient.nack(msg, true);
      }
    }, true);
  }

  stopProcessing(): void {
    this.isProcessing = false;
    console.log('Stopping email processor...');
  }

  private async processEmail(emailData: EmailQueueItem): Promise<void> {
    const startTime = Date.now();
    let parsed: ParsedMail | null = null;

    try {
      console.log(`Processing email from ${emailData.account_email}...`);

      parsed = await this.emailParser.parse(emailData.raw_email);

      if (!parsed || !parsed.from) {
        console.warn(`Email without sender, ignoring...`);
        return;
      }

      const email = parsed;

      const attachments = [];
      if (email.attachments && email.attachments.length > 0) {
        for (const att of email.attachments) {
          attachments.push({
            filename: att.filename || 'anexo',
            mime_type: att.contentType || 'application/octet-stream',
            size: att.size || 0,
            content: (att.content as Buffer).toString('base64'),
          });
        }
      }

      const payload = {
        email_account: emailData.account_email,
        from: this.extractFromAddress(email),
        to: this.extractAddresses(email.to),
        cc: this.extractAddresses(email.cc),
        bcc: this.extractAddresses(email.bcc),
        subject: email.subject || '(No subject)',
        body: email.html || email.text || '',
        thread_id: this.extractThreadId(email),
        reply_to: email.replyTo ? this.extractAddresses(email.replyTo)[0] || null : null,
        external_id: email.messageId ? email.messageId.replace(/[<>]/g, '') : null,
        processed_at: email.date ? email.date.toISOString().replace('T', ' ').substring(0, 19) : null,
        attachments: attachments.length > 0 ? attachments : undefined,
        complements: null,
      };

      await this.httpClient.post('/internal/save-email', payload);

      const duration = Date.now() - startTime;
      console.log(
        `Email saved: "${email.subject || '(No subject)'}" [${emailData.account_email}] (${duration}ms)`
      );
    } catch (error: any) {
      console.error(`Error processing email [${emailData.account_email}]:`, error.message);
      throw error;
    }
  }

  private extractFromAddress(parsed: ParsedMail): string {
    if (parsed.from?.value && parsed.from.value.length > 0) {
      return parsed.from.value[0].address || 'unknown@unknown.com';
    }
    if (parsed.from?.text) {
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
      const emails: string[] = [];
      const regex = /([a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z0-9_-]+)/g;
      const matches = address.text.match(regex);
      if (matches) emails.push(...matches);
      return emails;
    }

    return [];
  }

  private extractThreadId(parsed: ParsedMail): string | null {
    if (parsed.references && parsed.references.length > 0) {
      for (const ref of parsed.references) {
        const match = ref.match(/<([^@]+)@superestagios\.com\.br>/);
        if (match) return match[1];
      }
    }

    if (parsed.inReplyTo) {
      const inReplyTo = Array.isArray(parsed.inReplyTo) ? parsed.inReplyTo[0] : parsed.inReplyTo;
      const match = inReplyTo.match(/<([^@]+)@/);
      if (match) return match[1];
    }

    if (parsed.messageId) {
      const match = parsed.messageId.match(/<([^@]+)@/);
      if (match) return match[1];
      return parsed.messageId.replace(/[<>]/g, '');
    }

    return null;
  }
}
