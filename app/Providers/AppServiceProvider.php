<?php

namespace App\Providers;

use App\Domain\Repositories\EmailQueueRepositoryInterface;
use App\Domain\Repositories\EmailRepositoryInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Services\EmailSenderInterface;
use App\Infrastructure\Persistence\EmailQueueRepository;
use App\Infrastructure\Persistence\EmailRepository;
use App\Infrastructure\Persistence\UserRepository;
use App\Infrastructure\Services\EmailSenderService;
use App\UseCases\Auth\AuthUseCase;
use App\UseCases\Auth\AuthUseCaseInterface;
use App\UseCases\ListEmails\ListEmailsUseCase;
use App\UseCases\ListEmails\ListEmailsUseCaseInterface;
use App\UseCases\SendBatch\SendBatchUseCase;
use App\UseCases\SendBatch\SendBatchUseCaseInterface;
use App\UseCases\SendEmail\SendEmailUseCase;
use App\UseCases\SendEmail\SendEmailUseCaseInterface;
use App\UseCases\StoreEmail\StoreBatchUseCase;
use App\UseCases\StoreEmail\StoreBatchUseCaseInterface;
use App\UseCases\SwitchAccount\SwitchAccountUseCase;
use App\UseCases\SwitchAccount\SwitchAccountUseCaseInterface;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(EmailRepositoryInterface::class, EmailRepository::class);
        $this->app->bind(SendEmailUseCaseInterface::class, SendEmailUseCase::class);
        $this->app->bind(AuthUseCaseInterface::class, AuthUseCase::class);
        $this->app->bind(SwitchAccountUseCaseInterface::class, SwitchAccountUseCase::class);
        $this->app->bind(StoreBatchUseCaseInterface::class, StoreBatchUseCase::class);
        $this->app->bind(SendBatchUseCaseInterface::class, SendBatchUseCase::class);
        $this->app->bind(EmailSenderInterface::class, EmailSenderService::class);
        $this->app->bind(EmailQueueRepositoryInterface::class, EmailQueueRepository::class);
        $this->app->bind(ListEmailsUseCaseInterface::class, ListEmailsUseCase::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url') . "/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}
