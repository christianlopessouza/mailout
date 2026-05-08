<?php

use App\Infrastructure\Support\EmailFiltersMapper;
use App\Infrastructure\Support\FilterRegistry;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesFolderFilter;
use App\Domain\Contracts\IFilter;
use Illuminate\Database\Query\Builder;
use Mockery;

it('should resolve filters from DTO using registry', function () {
    // Setup
    $mockFilter = Mockery::mock(IFilter::class);
    $registry = new FilterRegistry();
    $registry->register('folder', $mockFilter);
    
    $mapper = new EmailFiltersMapper($registry);
    
    $dto = new class {
        use \Spatie\LaravelData\Concerns\WireableData; // just to make it a data-like object
        #[App\Infrastructure\Support\Filter('folder')]
        public ?string $folder_slug = 'sent';
    };

    // Execution
    $filters = $mapper->resolveFiltersFromDTO($dto);

    // Assertion
    expect($filters)->toHaveCount(1)
        ->and($filters[0][0])->toBe($mockFilter)
        ->and($filters[0][1])->toBe('sent');
});
