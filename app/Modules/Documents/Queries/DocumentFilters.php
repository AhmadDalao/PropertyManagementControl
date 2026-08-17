<?php

namespace App\Modules\Documents\Queries;

use App\Models\Document;
use App\Models\User;
use App\Modules\Documents\Support\DocumentAttachments;
use App\Modules\Documents\Support\DocumentExpiryState;
use App\Modules\Documents\Support\DocumentOptions;
use App\Modules\Shared\TableQuery;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class DocumentFilters
{
    public function __construct(
        private readonly DocumentAttachments $attachments,
        private readonly DocumentExpiryState $expiry,
        private readonly DocumentPropertyScope $properties,
        private readonly DocumentRelatedSearch $related,
        private readonly TableQuery $tables,
    ) {}

    /** @return array<string, mixed> */
    public function fromRequest(Request $request): array
    {
        $filters = $this->tables->filters($request, [
            'type' => 'all',
            'attachment' => 'all',
            'visibility' => 'all',
            'expiry' => 'all',
            'date_from' => '',
            'date_to' => '',
            'property_id' => 'all',
        ]);

        foreach ([
            'type' => DocumentOptions::TYPES,
            'attachment' => DocumentOptions::ATTACHMENTS,
            'visibility' => DocumentOptions::VISIBILITIES,
            'expiry' => DocumentExpiryState::FILTERS,
        ] as $field => $allowed) {
            if (! in_array($filters[$field], ['all', ...$allowed], true)) {
                $filters[$field] = 'all';
            }
        }

        foreach (['date_from', 'date_to'] as $field) {
            if (! $this->validDate((string) $filters[$field])) {
                $filters[$field] = '';
            }
        }

        return $filters;
    }

    /**
     * @param  Builder<Document>  $documents
     * @param  array<string, mixed>  $filters
     */
    public function apply(Builder $documents, array $filters, User $actor): void
    {
        $this->tables->exact($documents, $filters, 'portfolio_id');
        $this->properties->apply($documents, $filters, $actor);
        $this->tables->exact($documents, $filters, 'type');
        $this->tables->dateRange($documents, $filters, 'created_at');

        if ($filters['attachment'] !== 'all') {
            $documents->whereIn(
                'documentable_type',
                $this->attachments->typesFor((string) $filters['attachment']),
            );
        }

        if ($filters['visibility'] !== 'all') {
            $documents->where('is_public', $filters['visibility'] === 'public');
        }

        $this->expiry->apply($documents, (string) $filters['expiry']);

        $this->tables->search($documents, (string) $filters['search'], [
            'title_en',
            'title_ar',
            'original_name',
            'type',
            fn (Builder $query, string $search, string $like) => $this->related->apply($query, $like),
        ]);
    }

    /**
     * @param  Builder<Document>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyScope(Builder $query, array $filters, User $actor): void
    {
        $this->tables->exact($query, $filters, 'portfolio_id');
        $this->properties->apply($query, $filters, $actor);
    }

    private function validDate(string $value): bool
    {
        if ($value === '') {
            return true;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
