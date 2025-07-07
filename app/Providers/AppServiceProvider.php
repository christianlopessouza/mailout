<?php

namespace App\Providers;

use App\Domain\Entities\Client;
use App\Infrastructure\Persistence\AccountRepository;
use App\Infrastructure\Persistence\ClientRepository;
use App\Infrastructure\Persistence\EmailComplementRepository;
use App\Infrastructure\Persistence\EmailComplementTemplateRepository;
use App\Infrastructure\Persistence\EmailRepository;
use App\Infrastructure\Persistence\Facades\FacadesAccountRepository;
use App\Infrastructure\Persistence\Facades\FacadesClientRepository;
use App\Infrastructure\Persistence\Facades\FacadesEmailComplementRepository;
use App\Infrastructure\Persistence\Facades\FacadesEmailComplementTemplateRepository;
use App\Infrastructure\Persistence\Facades\FacadesEmailRepository;
use App\Infrastructure\Persistence\Facades\FacadesFolderRepository;
use App\Infrastructure\Persistence\FolderRepository;
use App\Infrastructure\Services\EmailSenderService;
use App\Infrastructure\Services\SymfonyEmailSenderService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(EmailSenderService::class, SymfonyEmailSenderService::class);
        $this->app->bind(EmailRepository::class,  FacadesEmailRepository::class);
        $this->app->bind(FolderRepository::class, FacadesFolderRepository::class);
        $this->app->bind(AccountRepository::class, FacadesAccountRepository::class);
        $this->app->bind(ClientRepository::class, FacadesClientRepository::class);
        $this->app->bind(EmailComplementRepository::class, FacadesEmailComplementRepository::class);
        $this->app->bind(EmailComplementTemplateRepository::class, FacadesEmailComplementTemplateRepository::class);
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
