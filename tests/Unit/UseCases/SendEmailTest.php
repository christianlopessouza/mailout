<?php

use App\UseCases\SendEmail;
use App\UseCases\Services\SendEmailService;
use App\Data\Input\SendEmailInputData;
use Mockery;

it('should execute send email service', function () {
    // Setup
    $serviceMock = Mockery::mock(SendEmailService::class);
    $sendEmail = new SendEmail($serviceMock);
    $inputMock = Mockery::mock(SendEmailInputData::class);
    $inputMock->account = Mockery::mock();
    $inputMock->email = Mockery::mock();
    $inputMock->email->shouldReceive('toArray')->andReturn([]);
    
    $responseMock = (object)['email' => Mockery::mock()];
    
    $serviceMock->shouldReceive('execute')
        ->once()
        ->andReturn($responseMock);
        
    // Execution
    $result = $sendEmail->execute($inputMock);
    
    // Assertion
    expect($result->email)->toBe($responseMock->email);
});
