<?php

namespace App\Infrastructure\Services;

interface RabbitMQService
{
    public function publish(string $queue, array $data): void;
}
