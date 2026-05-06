import amqp, { Channel, ChannelModel, Connection, Message } from "amqplib";
import { IQueueClient } from "../interfaces/IQueueClient";

export class RabbitMQAdapter implements IQueueClient {
    private constructor(
        private connection: ChannelModel,
        private channel: Channel,
    ) {}

    static async create(url: string): Promise<RabbitMQAdapter> {
        const connection = await amqp.connect(url);
        const channel = await connection.createChannel();
        return new RabbitMQAdapter(connection, channel);
    }

    async push(queueName: string, data: any): Promise<void> {
        if (!this.channel) throw new Error("RabbitMQ channel not initialized");

        await this.channel.assertQueue(queueName, { durable: true });
        this.channel.sendToQueue(queueName, Buffer.from(JSON.stringify(data)), {
            persistent: true,
        });
    }

    async consume(
        queueName: string,
        callback: (msg: Message | null) => Promise<void>,
        useDLQ: boolean = false,
    ): Promise<void> {
        if (!this.channel) throw new Error("RabbitMQ channel not initialized");

        let queueOptions: any = { durable: true };

        if (useDLQ) {
            const DLX = "email_dlx";
            const DLQ = "email_processing_dlq";

            await this.channel.assertExchange(DLX, "direct", { durable: true });
            await this.channel.assertQueue(DLQ, { durable: true });
            await this.channel.bindQueue(DLQ, DLX, queueName);

            queueOptions.arguments = {
                "x-dead-letter-exchange": DLX,
                "x-dead-letter-routing-key": queueName,
            };
        }

        await this.channel.assertQueue(queueName, queueOptions);
        await this.channel.prefetch(1);

        this.channel.consume(queueName, async (msg: Message | null) => {
            if (msg) {
                await callback(msg);
            }
        });
    }

    ack(msg: any): void {
        if (!this.channel) throw new Error("RabbitMQ channel not initialized");
        this.channel.ack(msg as Message);
    }

    nack(msg: any, requeue: boolean): void {
        if (!this.channel) throw new Error("RabbitMQ channel not initialized");
        this.channel.nack(msg as Message, false, requeue);
    }

    async close(): Promise<void> {
        if (this.channel) await this.channel.close();
        if (this.connection.clos) await this.connection.close();
    }
}
