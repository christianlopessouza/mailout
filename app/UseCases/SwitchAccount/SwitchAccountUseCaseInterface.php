<?php

namespace App\UseCases\SwitchAccount;

interface SwitchAccountUseCaseInterface
{
    public function execute(SwitchAccountRequest $request): SwitchAccountResponse;
}