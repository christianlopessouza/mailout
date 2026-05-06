<?php

namespace App\Infrastructure\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQService
{
    private $connection;
    private $channel;

    public function __construct()
    {
        $this->connection = new AMQPStreamConnection(
            env('RABBITMQ_HOST', 'rabbitmq'),
            env('RABBITMQ_PORT', 5672),
            env('RABBITMQ_USER', 'guest'),
            env('RABBITMQ_PASS', 'guest')
        );
        $this->channel = $this->connection->channel();
    }

    public function publish(string $queue, array $data)
    {
        $this->channel->queue_declare($queue, false, true, false, false);
        $msg = new AMQPMessage(json_encode($data), ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
        $this->channel->basic_publish($msg, '', $queue);
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
