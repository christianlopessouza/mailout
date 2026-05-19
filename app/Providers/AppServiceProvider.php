<?php

namespace App\Providers;

use App\Infrastructure\Persistence\AccountRepository;
use App\Infrastructure\Persistence\AttachmentRepository;
use App\Infrastructure\Persistence\ClientRepository;
use App\Infrastructure\Persistence\EmailComplementRepository;
use App\Infrastructure\Persistence\EmailComplementTemplateRepository;
use App\Infrastructure\Persistence\EmailRepository;
use App\Infrastructure\Persistence\Facades\FacadesAccountRepository;
use App\Infrastructure\Persistence\Facades\FacadesAttachmentRepository;
use App\Infrastructure\Persistence\Facades\FacadesClientRepository;
use App\Infrastructure\Persistence\Facades\FacadesEmailComplementRepository;
use App\Infrastructure\Persistence\Facades\FacadesEmailComplementTemplateRepository;
use App\Infrastructure\Persistence\Facades\FacadesEmailRepository;
use App\Infrastructure\Persistence\Facades\FacadesFolderRepository;
use App\Infrastructure\Persistence\FolderRepository;
use App\Domain\Contracts\IAttachmentService;
use App\Domain\Contracts\IEmailAuthenticationService;
use App\Domain\Contracts\IEmailSenderService;
use App\Infrastructure\Adapters\S3AttachmentAdapter;
use App\Infrastructure\Adapters\RabbitMQAdapter;
use App\Infrastructure\Adapters\SymfonyEmailAuthenticationAdapter;
use App\Infrastructure\Adapters\SymfonyEmailSenderAdapter;
use App\UseCases\FilterEmailsByAccount;
use App\UseCases\FilterEmailsByClient;
use App\Infrastructure\Support\EmailFiltersMapper;
use App\Infrastructure\Support\FilterRegistry;
use App\Infrastructure\Services\RabbitMQService;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesFolderFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesProcessDateFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesReadFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesBodyFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesSubjectFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesAddressFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesDirectionFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesComplementsFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesAccountIdFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesFlagsFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesOrderFilter;
use Aws\S3\S3Client;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(IEmailSenderService::class, SymfonyEmailSenderAdapter::class);
        $this->app->bind(EmailRepository::class,  FacadesEmailRepository::class);
        $this->app->bind(FolderRepository::class, FacadesFolderRepository::class);
        $this->app->bind(AccountRepository::class, FacadesAccountRepository::class);
        $this->app->bind(ClientRepository::class, FacadesClientRepository::class);
        $this->app->bind(EmailComplementRepository::class, FacadesEmailComplementRepository::class);
        $this->app->bind(EmailComplementTemplateRepository::class, FacadesEmailComplementTemplateRepository::class);
        $this->app->bind(IAttachmentService::class, function () {
            $config = config('services.s3');
            $s3Client = new S3Client($config);

            return new S3AttachmentAdapter($s3Client);
        });
        $this->app->bind(AttachmentRepository::class, FacadesAttachmentRepository::class);
        $this->app->bind(IEmailAuthenticationService::class, SymfonyEmailAuthenticationAdapter::class);
        $this->app->bind(RabbitMQService::class, RabbitMQAdapter::class);
        $this->app->bind(FilterEmailsByAccount::class, FilterEmailsByAccount::class);
        $this->app->bind(FilterEmailsByClient::class, FilterEmailsByClient::class);
        $this->app->singleton(FilterRegistry::class, function ($app) {
            $registry = new FilterRegistry();
            $registry->register('folder', $app->make(FacadesFolderFilter::class));
            $registry->register('process_date', $app->make(FacadesProcessDateFilter::class));
            $registry->register('read_date', $app->make(FacadesReadFilter::class));
            $registry->register('body', $app->make(FacadesBodyFilter::class));
            $registry->register('subject', $app->make(FacadesSubjectFilter::class));
            $registry->register('address', $app->make(FacadesAddressFilter::class));
            $registry->register('direction', $app->make(FacadesDirectionFilter::class));
            $registry->register('complements', $app->make(FacadesComplementsFilter::class));
            $registry->register('account', $app->make(FacadesAccountIdFilter::class));
            $registry->register('flags', $app->make(FacadesFlagsFilter::class));
            $registry->register('order', $app->make(FacadesOrderFilter::class));
            return $registry;
        });

        $this->app->bind(EmailFiltersMapper::class, function ($app) {
            return new EmailFiltersMapper($app->make(FilterRegistry::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url') . "/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        // Log de todas as queries SQL (apenas em ambiente local/debug)
        if (config('app.debug')) {
            DB::listen(function ($query) {
                $queryCompleta = vsprintf(
                    str_replace('?', '%s', $query->sql),
                    collect($query->bindings)->map(function ($binding) {
                        if (is_null($binding)) {
                            return 'NULL';
                        }
                        if (is_bool($binding)) {
                            return $binding ? 'true' : 'false';
                        }
                        if (is_numeric($binding)) {
                            return $binding;
                        }
                        if ($binding instanceof \DateTime || $binding instanceof \DateTimeInterface) {
                            return "'" . $binding->format('Y-m-d H:i:s') . "'";
                        }
                        if (is_object($binding)) {
                            return "'" . (string) $binding . "'";
                        }
                        return "'{$binding}'";
                    })->toArray()
                );
                
                $output = "\n" . str_repeat('=', 80) . "\n";
                $output .= "SQL QUERY [{$query->time}ms]\n";
                $output .= str_repeat('-', 80) . "\n";
                $output .= "SQL: {$query->sql}\n";
                $output .= "Bindings: " . json_encode($query->bindings) . "\n";
                $output .= str_repeat('-', 80) . "\n";
                $output .= "Query Completa:\n{$queryCompleta}\n";
                $output .= str_repeat('=', 80) . "\n";
                
                // Escreve direto no STDERR (aparece no terminal)
                fwrite(STDERR, $output);
            });
        }
    }
}
