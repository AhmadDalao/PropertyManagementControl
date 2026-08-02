<?php

namespace App\Modules\ActionCenter\Queries;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\User;
use App\Modules\ActionCenter\Contracts\ActionCenterSource;
use App\Modules\ActionCenter\Support\ActionCenterAssignee;
use App\Modules\ActionCenter\Support\ActionCenterItemState;
use App\Modules\Documents\Queries\DocumentDirectoryQuery;
use App\Modules\Documents\Queries\DocumentFilters;
use App\Modules\Documents\Support\DocumentExpiryState;
use App\Modules\Documents\Support\DocumentOptions;
use Illuminate\Database\Eloquent\Builder;

final readonly class DocumentExpiryActionSource implements ActionCenterSource
{
    public function __construct(
        private DocumentDirectoryQuery $directory,
        private DocumentFilters $filters,
        private DocumentExpiryState $expiry,
        private ActionCenterAssignee $assignees,
        private ActionCenterItemState $itemState,
    ) {}

    public function type(): string
    {
        return 'document_expiry';
    }

    public function module(): string
    {
        return 'documents';
    }

    public function count(User $actor, array $filters): int
    {
        return $this->query($actor, $filters)->count();
    }

    public function items(User $actor, array $filters, int $limit): array
    {
        if ($limit < 1) {
            return [];
        }

        $documents = $this->query($actor, $filters)
            ->with([
                'portfolio:id,name_en,name_ar',
                'uploadedBy:id,name',
                'documentable',
            ])
            ->orderBy('expires_on')
            ->limit($limit)
            ->get();

        $documents->loadMorph('documentable', [
            Asset::class => [],
            Lease::class => ['tenantProfile.user', 'leaseable'],
            Payment::class => ['tenantProfile.user', 'lease.leaseable'],
        ]);

        return array_values($documents
            ->map(fn (Document $document): array => $this->item($document))
            ->all());
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Document>
     */
    private function query(User $actor, array $filters): Builder
    {
        $query = $this->directory->base($actor);
        $this->filters->apply($query, [
            ...$filters,
            'type' => 'all',
            'attachment' => 'all',
            'visibility' => 'all',
            'expiry' => 'attention',
            'date_from' => '',
            'date_to' => '',
        ], $actor);
        $this->applyAssignee($query, $filters, $actor);
        $this->applyPriority($query, (string) ($filters['priority'] ?? 'all'));

        return $query;
    }

    /**
     * @param  Builder<Document>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyAssignee(Builder $query, array $filters, User $actor): void
    {
        $assignee = $this->assignees->value($filters, $actor);

        if ($assignee === 'unassigned') {
            $query->whereNull('uploaded_by_user_id');
        } elseif (is_int($assignee)) {
            $query->where('uploaded_by_user_id', $assignee);
        }
    }

    /** @param Builder<Document> $query */
    private function applyPriority(Builder $query, string $priority): void
    {
        match ($priority) {
            'critical' => $query->whereDate('expires_on', '<', today()),
            'high' => $query
                ->whereDate('expires_on', '>=', today())
                ->whereDate('expires_on', '<=', today()->addDays(30)),
            'normal' => $query
                ->whereDate('expires_on', '>', today()->addDays(30))
                ->whereDate('expires_on', '<=', today()->addDays(90)),
            default => null,
        };
    }

    /** @return array<string, mixed> */
    private function item(Document $document): array
    {
        [$asset, $tenant] = $this->context($document);
        $status = $this->expiry->code($document->expires_on);
        $priority = match ($status) {
            'expired' => 'critical',
            'due_30' => 'high',
            default => 'normal',
        };

        return [
            'key' => 'document-expiry:'.$document->id,
            'record_id' => $document->id,
            'type' => $this->type(),
            'priority' => $priority,
            'title' => app()->getLocale() === 'ar'
                ? ($document->title_ar ?: $document->title_en)
                : ($document->title_en ?: $document->title_ar),
            'subtitle' => DocumentOptions::label($document->type),
            'tenant' => $tenant,
            'asset' => $this->asset($asset),
            'portfolio' => $this->portfolio($document),
            'status' => $status,
            'due_on' => $document->expires_on?->toDateString(),
            'due_state' => $this->itemState->dueState($document->expires_on),
            'assigned_to' => $document->uploadedBy ? [
                'id' => $document->uploadedBy->id,
                'name' => $document->uploadedBy->name,
            ] : null,
            'amount' => null,
            'currency' => null,
            'href' => route('documents.show', $document, false),
            'is_showcase' => $document->getIsShowcaseAttribute(),
        ];
    }

    /** @return array{Asset|null,string|null} */
    private function context(Document $document): array
    {
        $record = $document->documentable;

        if ($record instanceof Asset) {
            return [$record, null];
        }

        if ($record instanceof Lease) {
            return [
                $record->leaseable instanceof Asset ? $record->leaseable : null,
                $record->tenantProfile?->user?->name,
            ];
        }

        if ($record instanceof Payment) {
            return [
                $record->lease?->leaseable instanceof Asset
                    ? $record->lease->leaseable
                    : null,
                $record->tenantProfile?->user?->name,
            ];
        }

        return [null, null];
    }

    /** @return array<string, mixed>|null */
    private function asset(?Asset $asset): ?array
    {
        return $asset ? [
            'id' => $asset->id,
            'title_en' => $asset->title_en,
            'title_ar' => $asset->title_ar,
            'code' => $asset->code,
        ] : null;
    }

    /** @return array<string, mixed>|null */
    private function portfolio(Document $document): ?array
    {
        return $document->portfolio ? [
            'id' => $document->portfolio->id,
            'name_en' => $document->portfolio->name_en,
            'name_ar' => $document->portfolio->name_ar,
        ] : null;
    }
}
