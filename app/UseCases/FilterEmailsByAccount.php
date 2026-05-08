<?php

namespace App\UseCases;

use App\Data\Input\FilterEmailsByAccountInputData;
use App\Data\Output\FilterEmailsOutputData;
use App\Data\PaginationData;
use App\Infrastructure\Persistence\EmailRepository;
use App\Infrastructure\Support\EmailFiltersMapper;

class FilterEmailsByAccount
{
    public function __construct(
        private readonly EmailRepository $emailRepository,
        private readonly EmailFiltersMapper $emailFiltersService
    ) {
    }

    public function execute(FilterEmailsByAccountInputData $input): FilterEmailsOutputData
    {
        $filter = $input->filter;
        $account = $input->account;

        $filtersToApply = $this->emailFiltersService->resolve($filter);

        $pagination = PaginationData::validateAndCreate([
            'perPage' => $filter->limit_per_page,
            'page' => $filter->page ?: 1
        ]);

        $emails = $this->emailRepository->findByAccount($account->getId(), $filtersToApply, $pagination);

        return new FilterEmailsOutputData(
            emails: $emails->items,
            total: $emails->total
        );
    }
}