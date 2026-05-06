import { IHttpClient } from '../interfaces/IHttpClient';
import { AccountConfig } from '../types/account.types';

export class AccountFetcherService {
  private httpClient: IHttpClient;

  constructor(httpClient: IHttpClient) {
    this.httpClient = httpClient;
  }

  async fetchActiveAccounts(): Promise<AccountConfig[]> {
    try {
      console.log('Fetching active accounts...');
      
      const response = await this.httpClient.get<any[]>('/accounts/active');
      
      if (!Array.isArray(response)) {
        console.warn('Invalid API response');
        return [];
      }

      const accounts: AccountConfig[] = response.map((acc: any) => ({
        id: acc.id,
        email: acc.email_address,
        password: acc.password,
        host: acc.host || 'mail.gruposuper.com.br',
        port: acc.port || 993,
        secure: true,
        username: acc.username || acc.email_address,
      }));

      console.log(`${accounts.length} account(s) found`);
      return accounts;
    } catch (error: any) {
      console.error('Error fetching accounts:', error.message);
      return [];
    }
  }

  async refreshAccounts(): Promise<AccountConfig[]> {
    return this.fetchActiveAccounts();
  }
}
