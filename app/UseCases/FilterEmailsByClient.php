<?php

namespace App\UseCases;

use App\Data\Input\FilterEmailsByClientInputData;
use App\Data\Output\FilterEmailsOutputData;
use App\Data\PaginationData;
use App\Infrastructure\Persistence\EmailRepository;
use App\Infrastructure\Support\EmailFiltersMapper;

class FilterEmailsByClient
{
    public function __construct(
        private readonly EmailRepository $emailRepository,
        private readonly EmailFiltersMapper $emailFiltersService
    ) {}

    public function execute(FilterEmailsByClientInputData $input): FilterEmailsOutputData
    {
        $client = $input->client;
        $filter = $input->filter;

        if (!empty($filter->flag_names)) {
            throw new \InvalidArgumentException('Flag names are not supported for client filtering.');
        }

        $filtersToApply = $this->emailFiltersService->resolve($filter);

        $pagination = PaginationData::validateAndCreate([
            'perPage' => $filter->limit_per_page,
            'page' => $filter->page ?: 1
        ]);
        
        $emails = $this->emailRepository->findByClient($client->getDomain(), $filtersToApply, $pagination);
        
        return new FilterEmailsOutputData(
            emails: $emails->items,
            total: $emails->total
        );
    }
}