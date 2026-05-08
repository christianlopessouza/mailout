<?php

use App\Domain\Services\EmailComplementService;
use App\Infrastructure\Persistence\EmailComplementTemplateRepository;
use App\Domain\Entities\EmailComplementTemplate;
use Mockery;

it('should apply template and return resolved complements', function () {
    // Setup
    $repoMock = Mockery::mock(EmailComplementTemplateRepository::class);
    $templateObj = (object)['value' => ['string']];
    $template = Mockery::mock(EmailComplementTemplate::class);
    $template->template = $templateObj;
    
    $repoMock->shouldReceive('findByClientId')
        ->once()
        ->with('client_id')
        ->andReturn($template);
    
    $service = new EmailComplementService($repoMock);
    
    $complements = (object)['value' => 'my_value'];
    
    // Execution
    $result = $service->applyTemplateAndSave($complements, 'client_id');
    
    // Assertion
    expect($result->value)->toBe('my_value');
});
