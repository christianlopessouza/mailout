export interface AccountConfig {
  id: string;
  email: string;
  password: string;
  host: string;
  port: number;
  secure: boolean;
  username?: string;
}

export interface EmailQueueItem {
  account_id: string;
  account_email: string;
  raw_email: string;
  received_at: string;
}

