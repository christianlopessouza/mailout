import axios, { AxiosInstance } from 'axios';
import { IHttpClient } from '../interfaces/IHttpClient';

export class AxiosAdapter implements IHttpClient {
  private apiClient: AxiosInstance;

  constructor(apiUrl: string, auth?: { username?: string; password?: string; token?: string }) {
    this.apiClient = axios.create({
      baseURL: apiUrl,
      timeout: 30000,
      headers: {
        'Content-Type': 'application/json',
        ...(auth?.token && { 'Authorization': `Bearer ${auth.token}` }),
      },
      auth: auth?.username && auth?.password ? { username: auth.username, password: auth.password } : undefined,
    });
  }

  async get<T>(url: string): Promise<T> {
    const response = await this.apiClient.get(url);
    return response.data;
  }

  async post<T>(url: string, data: any): Promise<T> {
    const response = await this.apiClient.post(url, data);
    return response.data;
  }
}
