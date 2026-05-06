import { IHttpClient } from '../interfaces/IHttpClient';

export class QueueMonitorService {
  private httpClient: IHttpClient;
  private readonly QUEUE_NAME = 'email_processing_queue';
  private readonly THRESHOLD = 100;

  constructor(httpClient: IHttpClient) {
    this.httpClient = httpClient;
  }

  async checkQueueSize(): Promise<void> {
    try {
      // RabbitMQ Management API endpoint
      const queueInfo: any = await this.httpClient.get(`/api/queues/%2f/${this.QUEUE_NAME}`);
      
      const messageCount = queueInfo.messages || 0;

      if (messageCount > this.THRESHOLD) {
        console.error(`ALERT: Queue ${this.QUEUE_NAME} is critical: ${messageCount} messages pending!`);
      } else {
        console.log(`Queue ${this.QUEUE_NAME} healthy: ${messageCount} messages.`);
      }
    } catch (error: any) {
      console.error('Error monitoring queue size:', error.message);
    }
  }
}
