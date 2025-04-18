<?php

use App\Domain\Services\EmailSenderService;
use App\UseCases\SendEmail;

describe('Send email', function () {
    it('should send email', function () {


        $emailSenderService = Mockery::mock(EmailSenderService::class);
        $emailSenderService->shouldReceive('send')
            ->andReturnTrue()
            ->getMock();

        new SendEmail(
            emailSenderService: $emailSenderService,
            emailRepository: $emailRepository,
            folderRepository: $folderRepository
        );
    });
});
