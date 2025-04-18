<?php

namespace App\UseCases\Auth;

use App\Domain\Repositories\UserRepositoryInterface;
use App\UseCases\Auth\AuthUseCaseInterface;
use App\UseCases\Auth\AuthUseCaseRequest;
use App\UseCases\Auth\AuthUseCaseResponse;

class AuthUseCase implements AuthUseCaseInterface
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function execute(AuthUseCaseRequest $authUseCaseRequest): AuthUseCaseResponse
    {
        $username = $authUseCaseRequest->getUsername();
        $password = $authUseCaseRequest->getPassword();

        $user = $this->userRepository->findByUsername($username);

        if (!$user || $user->getPassword() !== $password) {
            throw new \Exception('Usuário ou senha inválidos.');
        }

        $emailAccounts = $user->getEmailAccounts();
        $default_email = $user->getDefaultEmail()->getEmail();

        return new AuthUseCaseResponse($user, $emailAccounts, $default_email);
    }
}