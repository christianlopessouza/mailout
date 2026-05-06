export interface IImapClient {
  connect(): Promise<void>;
  search(criteria: any[]): Promise<number[]>;
  fetch(results: number[], options: any): any;
  setFlags(results: number[], flags: string[]): Promise<void>;
  idle(): Promise<void>;
  idleStop(): void;
  disconnect(): void;
  on(event: string, listener: (...args: any[]) => void): void;
}
