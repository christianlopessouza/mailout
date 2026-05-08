<?php

use App\Domain\Services\EmailQueueService;
use App\Infrastructure\Persistence\Facades\FacadesEmailQueueRepository;
use App\Domain\Entities\EmailQueue;
use Mockery;

it('should save email to queue', function () {
    // Setup
    $repoMock = Mockery::mock(FacadesEmailQueueRepository::class);
    $repoMock->shouldReceive('save')
        ->once()
        ->with(Mockery::type(EmailQueue::class))
        ->andReturn(true);
    
    $service = new EmailQueueService($repoMock);
    
    $emailData = [
        'from' => 'test@example.com',
        'to' => ['recipient@example.com'],
        'subject' => 'Subject',
        'body' => 'Body'
    ];
    
    // Execution
    $result = $service->saveEmailToQueue($emailData);
    
    // Assertion
    expect($result)->toBeTrue();
});
