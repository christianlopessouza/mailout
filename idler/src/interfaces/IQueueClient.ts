export interface IQueueClient {
  push(queueName: string, data: any): Promise<void>;
  consume(
    queueName: string,
    callback: (msg: any) => Promise<void>,
    useDLQ?: boolean
  ): Promise<void>;
  ack(msg: any): void;
  nack(msg: any, requeue: boolean): void;
}
