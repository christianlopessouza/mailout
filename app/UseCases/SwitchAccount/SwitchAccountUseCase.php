<?php

namespace App\UseCases\SwitchAccount;

use App\JWT;

class SwitchAccountUseCase implements SwitchAccountUseCaseInterface
{
    public function execute(SwitchAccountRequest $switchAccountRequest): SwitchAccountResponse
    {
        $user = $switchAccountRequest->getUser();
        $emailId = $switchAccountRequest->getEmailId();

        if (!$emailId) {
            return response()->json([
                'error' => 'O ID do email não foi fornecido.'
            ], 422);
        }

        $emails = $user->getEmailAccounts();
        $switchEmail = '';
        foreach ($emails as $email) {
            if ($email->getId() == $emailId) {
                $switchEmail = $email;
                break;
            }
        }
        if (!$switchEmail) {
            return response()->json([
                'error' => 'E-mail não encontrado para este usuário.'
            ], 404);
        }

        $payload = [
            'usuario' => $user->getUsername(),
            'senha' => $user->getPassword(),
            'emailAtual' => $switchEmail->getEmail(),
        ];

        $secret = env('JWT_SECRET');
        $novoToken = JWT::encode($payload, $secret);

        return new SwitchAccountResponse($novoToken);
    }

}