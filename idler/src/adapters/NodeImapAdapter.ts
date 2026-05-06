import Imap from 'imap';
import { EventEmitter } from 'events';
import { IImapClient } from '../interfaces/IImapClient';

export class NodeImapAdapter extends EventEmitter implements IImapClient {
  private imap: Imap;

  constructor(config: any) {
    super();
    this.imap = new Imap(config);
    this.setupListeners();
  }

  private setupListeners() {
    this.imap.on('ready', () => this.emit('ready'));
    this.imap.on('error', (err) => this.emit('error', err));
    this.imap.on('end', () => this.emit('end'));
    this.imap.on('mail', (num) => this.emit('mail', num));
  }

  async connect(): Promise<void> {
    return new Promise((resolve, reject) => {
      this.imap.once('ready', resolve);
      this.imap.once('error', reject);
      this.imap.connect();
    });
  }

  async search(criteria: any[]): Promise<number[]> {
    return new Promise((resolve, reject) => {
      this.imap.search(criteria, (err, results) => {
        if (err) reject(err);
        else resolve(results);
      });
    });
  }

  fetch(results: number[], options: any): any {
    return this.imap.fetch(results, options);
  }

  async setFlags(results: number[], flags: string[]): Promise<void> {
    return new Promise((resolve, reject) => {
      this.imap.setFlags(results, flags, (err) => {
        if (err) reject(err);
        else resolve();
      });
    });
  }

  async idle(): Promise<void> {
    return new Promise((resolve, reject) => {
      (this.imap as any).idle((err: any) => {
        if (err) reject(err);
        else resolve();
      });
    });
  }

  idleStop(): void {
    (this.imap as any).idle_stop();
  }

  disconnect(): void {
    this.imap.end();
  }
}
