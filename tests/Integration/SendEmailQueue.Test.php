<?php

use App\Errors\EmailSendFailureError;
use App\UseCases\SendEmail;
use App\Domain\Entities\Account;
use App\Domain\Entities\Folder;
use App\Data\Input\SendEmailInputData;
use App\Domain\Enums\Folder as FolderType;
use App\Infrastructure\Persistence\Facades\FacadesAccountRepository;
use App\Infrastructure\Persistence\Facades\FacadesFolderRepository;
use App\Infrastructure\Persistence\Facades\FacadesEmailRepository;
use App\Infrastructure\Persistence\Facades\FacadesEmailQueueRepository;
use App\Infrastructure\Services\EmailSenderService;
use App\Infrastructure\Services\SymfonyEmailSenderService;
use App\Util\UUID;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class);

// describe('Send Email Queue', function () {
//     beforeEach(function () {
//         DB::table('accounts')->delete();
//         DB::table('folders')->delete();
//         DB::table('emails')->delete();
//         DB::table('email_queue')->delete();

//         $this->account = Account::create("");
//     }
// });