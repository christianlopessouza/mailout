import axios, { AxiosInstance } from 'axios';
import { AccountConfig } from '../types/account.types';

export class AccountFetcherService {
  private apiClient: AxiosInstance;

  constructor(apiUrl: string, apiToken?: string) {
    this.apiClient = axios.create({
      baseURL: apiUrl,
      timeout: 30000,
      headers: {
        'Content-Type': 'application/json',
        ...(apiToken && { 'Authorization': `Bearer ${apiToken}` }),
      },
    });
  }

  async fetchActiveAccounts(): Promise<AccountConfig[]> {
    try {
      console.log('📋 Buscando contas ativas...');
      
      const response = await this.apiClient.get('/accounts/active');
      
      if (!response.data || !Array.isArray(response.data)) {
        console.warn('⚠️ Resposta inválida da API');
        return [];
      }

      const accounts: AccountConfig[] = response.data.map((acc: any) => ({
        id: acc.id,
        email: acc.email_address,
        password: acc.password,
        host: acc.host || 'mail.gruposuper.com.br',
        port: acc.port || 993,
        secure: true,
        username: acc.username || acc.email_address,
      }));

      console.log(`✅ ${accounts.length} conta(s) encontrada(s)`);
      return accounts;
    } catch (error: any) {
      console.error('❌ Erro ao buscar contas:', error.message);
      if (error.response) {
        console.error('Status:', error.response.status);
        console.error('Data:', error.response.data);
      }
      return [];
    }
  }

  async refreshAccounts(): Promise<AccountConfig[]> {
    // Método para atualizar lista de contas periodicamente
    return this.fetchActiveAccounts();
  }
}

