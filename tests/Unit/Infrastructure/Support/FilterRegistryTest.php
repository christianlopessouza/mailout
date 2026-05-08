<?php

use App\Infrastructure\Support\FilterRegistry;
use App\Domain\Contracts\IFilter;
use Mockery;

it('should register and retrieve filter', function () {
    $registry = new FilterRegistry();
    $mockFilter = Mockery::mock(IFilter::class);
    
    $registry->register('test_key', $mockFilter);
    
    expect($registry->get('test_key'))->toBe($mockFilter)
        ->and($registry->get('non_existent'))->toBeNull();
});
