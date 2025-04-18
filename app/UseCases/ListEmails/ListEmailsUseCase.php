<?php

namespace App\UseCases\ListEmails;

use App\Domain\Enums\EmailFolderEnum;
use App\Domain\Enums\FilterTypeEnum;
use App\Domain\Repositories\EmailRepositoryInterface;

class ListEmailsUseCase implements ListEmailsUseCaseInterface
{
    public function __construct
    (
        private EmailRepositoryInterface $emailRepository
    ){}

    public function execute(ListEmailsRequest $request): ListEmailsResponse
    {
        $email = $request->getEmail();
        $filter_type = $request->getFilterType();
        $folder = $request->getFolder();
        $per_page = $request->getPerPage();
        $page = $request->getPage();
        $is_internal_request = $request->getIsInternal();

        if ($is_internal_request && $filter_type == null)
        {
            $filter_type = ($folder === EmailFolderEnum::SENT) ? FilterTypeEnum::FROM : FilterTypeEnum::TO;
        }

        $email_list = $this->emailRepository
            ->listEmails(
                $per_page,
                $page,
                $email,
                $filter_type,
                $folder,
            );

        return new ListEmailsResponse($email_list);
    }
}
