<?php

namespace App\UseCases\SendBatch;

interface SendBatchUseCaseInterface
{
    public function execute(SendBatchRequest $request): SendBatchResponse;
}