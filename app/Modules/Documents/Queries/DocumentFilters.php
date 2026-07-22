<?php

namespace App\Modules\Documents\Queries;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Lease;
use App\Models\Payment;
use App\Modules\Documents\Support\DocumentAttachments;
use App\Modules\Documents\Support\DocumentOptions;
use App\Modules\Shared\TableQuery;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class DocumentFilters
{
    public function __construct(
        private readonly DocumentAttachments $attachments,
        private readonly TableQuery $tables,
    ) {}

    /** @return array<string, mixed> */
    public function fromRequest(Request $request): array
    {
        $filters = $this->tables->filters($request, [
            'type' => 'all',
            'attachment' => 'all',
            'visibility' => 'all',
            'date_from' => '',
            'date_to' => '',
        ]);

        foreach ([
            'type' => DocumentOptions::TYPES,
            'attachment' => DocumentOptions::ATTACHMENTS,
            'visibility' => DocumentOptions::VISIBILITIES,
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
    public function apply(Builder $documents, array $filters): void
    {
        $this->tables->exact($documents, $filters, 'portfolio_id');
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

        $this->tables->search($documents, (string) $filters['search'], [
            'title_en',
            'title_ar',
            'original_name',
            'type',
            fn (Builder $query, string $search, string $like) => $this->relatedSearch($query, $like),
        ]);
    }

    /**
     * @param  Builder<Document>  $query
     * @param  array<string, mixed>  $filters
     */
    public function applyPortfolio(Builder $query, array $filters): void
    {
        $this->tables->exact($query, $filters, 'portfolio_id');
    }

    /**
     * @param  Builder<Document>  $documents
     * @return Builder<Document>
     */
    private function relatedSearch(Builder $documents, string $like): Builder
    {
        return $documents
            ->orWhereHas('uploadedBy', fn (Builder $users) => $users
                ->where('name', 'like', $like)
                ->orWhere('email', 'like', $like))
            ->orWhere(fn (Builder $leases) => $leases
                ->whereIn('documentable_type', $this->attachments->typesFor('lease'))
                ->whereIn('documentable_id', Lease::query()->select('id')->where('code', 'like', $like)))
            ->orWhere(fn (Builder $assets) => $assets
                ->whereIn('documentable_type', $this->attachments->typesFor('asset'))
                ->whereIn('documentable_id', Asset::query()
                    ->select('id')
                    ->where('title_en', 'like', $like)
                    ->orWhere('title_ar', 'like', $like)
                    ->orWhere('code', 'like', $like)))
            ->orWhere(fn (Builder $payments) => $payments
                ->whereIn('documentable_type', $this->attachments->typesFor('payment'))
                ->whereIn('documentable_id', Payment::query()->select('id')->where('reference', 'like', $like)));
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
