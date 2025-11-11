<?php

namespace App\Data;

use App\Domain\Enums\Direction;
use App\Infrastructure\Persistence\FilterBy;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesAccountIdFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesAddressFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesBodyFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesComplementsFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesDirectionFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesFlagsFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesFolderFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesOrderFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesProcessDateFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesReadFilter;
use App\Infrastructure\Persistence\Facades\EmailFilters\FacadesSubjectFilter;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class EmailFilterData extends Data
{
    public function __construct(
        #[FilterBy(FacadesFolderFilter::class)]
        public ?string $folder_slug = null,

        // These properties are tagged with the getter: getProcessDateFilter
        #[WithCast(DateTimeInterfaceCast::class, ['Y-m-d', \DateTime::ATOM])]
        public ?\DateTime $process_start_date = null,
        #[WithCast(DateTimeInterfaceCast::class, ['Y-m-d', \DateTime::ATOM])]
        public ?\DateTime $process_end_date = null,

        // These properties are tagged with the getter: getReadDateFilter
        public ?bool $read = null,
        #[WithCast(DateTimeInterfaceCast::class, ['Y-m-d', \DateTime::ATOM])]
        public ?\DateTime $read_start_date = null,
        #[WithCast(DateTimeInterfaceCast::class, ['Y-m-d', \DateTime::ATOM])]
        public ?\DateTime $read_end_date = null,

        #[FilterBy(FacadesBodyFilter::class)]
        public ?string $body_contains = null,

        #[FilterBy(FacadesSubjectFilter::class)]
        public ?string $subject_contains = null,

        #[FilterBy(FacadesAddressFilter::class)]
        public ?string $email_address = null,

        #[FilterBy(FacadesDirectionFilter::class)]
        public ?Direction $direction = null,

        #[FilterBy(FacadesComplementsFilter::class)]
        public ?array $complements = null,

        #[FilterBy(FacadesAccountIdFilter::class)]
        public ?array $account_id = null,

        #[FilterBy(FacadesFlagsFilter::class)]
        public ?array $flag_names = null,

        // These properties are tagged with the getter: getOrderFilter
        public ?string $order_by = null,
        public ?string $order = 'desc',

        public ?int $limit_per_page = 30,
        public ?int $page = null,
    ) {}

    public static function rules(?ValidationContext $context = null): array
    {
        return [
            'folder_slug' => ['nullable', 'string'],
            'process_start_date' => ['nullable', 'date'],
            'process_end_date' => ['nullable', 'date', 'after_or_equal:process_start_date'],
            'read' => [
                'nullable',
                'boolean',
                // This rule ensures 'read' is truthy if either read_start_date or read_end_date is provided
                Rule::when(
                    fn ($input) => !empty($input['read_start_date']) || !empty($input['read_end_date']),
                    ['required', 'accepted']
                )],
            'read_start_date' => ['nullable', 'date'],
            'read_end_date' => ['nullable', 'date', 'after_or_equal:read_start_date'],
            'body_contains' => ['nullable', 'string'],
            'subject_contains' => ['nullable', 'string'],
            'email_address' => ['nullable', 'string'],
            'order_by' => ['nullable', 'string', 'in:read,processed'],
            'order' => ['nullable', 'string', 'in:asc,desc'],
            'limit_per_page' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'complements' => ['nullable', 'array'],
            'complements.*' => ['required', function ($attribute, $value, $fail) {
                if (!is_object($value) && !is_array($value)) {
                    $fail('Each element in complements must be an object with key-value pairs.');
                }
            }],
            'account_id' => ['nullable', 'array'],
            'account_id.*' => ['required', 'string'],
            'flag_names' => ['nullable', 'array'],
            'flag_names.*' => ['string'],
        ];
    }

    #[Computed]
    #[FilterBy(FacadesProcessDateFilter::class)]
    public function getProcessDateFilter(): ?array
    {
        if (!$this->process_start_date && !$this->process_end_date) {
            return null;
        }

        return [
            'start' => $this->process_start_date?->format('Y-m-d'),
            'end' => $this->process_end_date?->format('Y-m-d'),
        ];
    }

    #[Computed]
    #[FilterBy(FacadesReadFilter::class)]
    public function getReadDateFilter(): ?array
    {
        if (!$this->read_start_date && !$this->read_end_date && $this->read === null) {
            return null;
        }

        return [
            'read' => $this->read ? true : false,
            'start' => $this->read_start_date?->format('Y-m-d'),
            'end' => $this->read_end_date?->format('Y-m-d'),
        ];
    }

    #[Computed]
    #[FilterBy(FacadesOrderFilter::class)]
    public function getOrderFilter(): ?array
    {
        if (!$this->order_by || !$this->order) {
            return null;
        }

        return [
            'by' => $this->order_by,
            'direction' => $this->order,
        ];
    }
}
