<?php

namespace App\UseCases\Auth;

interface AuthUseCaseInterface
{
    public function execute(AuthUseCaseRequest $authUseCaseRequest): AuthUseCaseResponse;
}