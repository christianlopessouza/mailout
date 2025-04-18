<?php

namespace App\UseCases\StoreEmail;

interface StoreBatchUseCaseInterface
{
    public function execute(StoreBatchRequest $request);
}