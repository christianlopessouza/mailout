<?php

namespace App\Providers;

use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesAddressFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesBodyFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesComplementsFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesDirectionFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesFlagsFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesFolderFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesOrderFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesProcessDateFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesReadFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesSubjectFilter;
use Illuminate\Support\ServiceProvider;

class EmailFilterServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->tag([
            FacadesFolderFilter::class,
            FacadesProcessDateFilter::class,
            FacadesReadFilter::class,
            FacadesBodyFilter::class,
            FacadesSubjectFilter::class,
            FacadesAddressFilter::class,
            FacadesDirectionFilter::class,
            FacadesComplementsFilter::class,
            FacadesFlagsFilter::class,
            FacadesOrderFilter::class,
        ], 'email.filters');
    }
}