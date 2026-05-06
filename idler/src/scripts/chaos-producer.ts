import amqp from 'amqplib';

async function runChaosTest() {
  const url = process.env.RABBITMQ_URL || 'amqp://guest:guest@localhost:5672';
  const connection = await amqp.connect(url);
  const channel = await connection.createChannel();
  const queue = 'email_processing_queue';

  await channel.assertQueue(queue, { durable: true });

  console.log('🧪 Injecting chaos test message...');
  channel.sendToQueue(queue, Buffer.from(JSON.stringify({ 
      account_id: 'chaos_1', 
      account_email: 'chaos@test.com', 
      raw_email: 'Test email content' 
  })), { persistent: true });

  console.log('✅ Message injected. You should now kill the worker process to verify ACK/NACK behavior.');
  
  await channel.close();
  await connection.close();
}

runChaosTest().catch(console.error);
