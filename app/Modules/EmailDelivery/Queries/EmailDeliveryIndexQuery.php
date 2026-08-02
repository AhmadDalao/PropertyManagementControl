<?php

namespace App\Modules\EmailDelivery\Queries;

use App\Models\EmailDeliveryLog;
use App\Models\User;
use App\Modules\EmailDelivery\Presenters\EmailDeliveryLogPresenter;
use App\Modules\EmailDelivery\Requests\EmailDeliveryIndexRequest;
use App\Modules\EmailDelivery\Support\EmailDeliveryAccess;
use App\Modules\EmailDelivery\Support\EmailDeliveryType;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;

final class EmailDeliveryIndexQuery
{
    public function __construct(
        private readonly EmailDeliveryAccess $access,
        private readonly EmailDeliveryLogPresenter $presenter,
        private readonly EmailDeliveryType $types,
        private readonly TableQuery $tables,
    ) {}

    /** @return array<string, mixed> */
    public function handle(EmailDeliveryIndexRequest $request, User $actor): array
    {
        $filters = $request->filters();
        $base = $this->base($actor);
        $filtered = clone $base;
        $this->applyFilters($filtered, $filters);

        $facet = clone $base;
        $this->applyFilters($facet, $filters, false);
        $counts = $this->counts($facet, $filters);

        $paginator = $this->tables
            ->paginate(
                $filtered->with(['portfolio', 'user']),
                $filters,
                ['created_at', 'status', 'email_type', 'recipient_email', 'attempts'],
            )
            ->through(fn (EmailDeliveryLog $log): array => $this->presenter->row($log));

        return [
            'deliveries' => $paginator,
            'filters' => $filters,
            'counts' => $counts,
            'insights' => $this->insights($counts),
            'typeOptions' => EmailDeliveryLog::query()
                ->distinct()
                ->orderBy('email_type')
                ->pluck('email_type')
                ->map(fn (string $type): array => [
                    'label' => $this->types->label($type),
                    'value' => $type,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<EmailDeliveryLog>
     */
    public function filtered(User $actor, array $filters): Builder
    {
        $query = $this->base($actor)->with(['portfolio', 'user']);
        $this->applyFilters($query, $filters);

        return $query;
    }

    /** @return Builder<EmailDeliveryLog> */
    private function base(User $actor): Builder
    {
        $this->access->ensureSuperadmin($actor);

        return EmailDeliveryLog::query();
    }

    /**
     * @param  Builder<EmailDeliveryLog>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters, bool $includeStatus = true): void
    {
        if ($includeStatus) {
            $this->tables->exact($query, $filters, 'status');
        }

        $this->tables->exact($query, $filters, 'email_type');
        $this->tables->dateRange($query, $filters, 'created_at');
        $this->tables->search($query, (string) ($filters['search'] ?? ''), [
            'recipient_email',
            'subject',
            'notification_id',
            'notification_class',
            'transport_message_id',
        ]);
    }

    /**
     * @param  Builder<EmailDeliveryLog>  $query
     * @param  array<string, mixed>  $filters
     * @return array<int, array{label:string,value:int,filter:array<string, string>,active:bool}>
     */
    private function counts(Builder $query, array $filters): array
    {
        $active = (string) ($filters['status'] ?? 'all');
        $counts = [[
            'label' => trans('app.email_delivery.statuses.all'),
            'value' => (clone $query)->count(),
            'filter' => ['status' => 'all'],
            'active' => $active === 'all',
        ]];

        foreach (['accepted', 'failed', 'processing'] as $status) {
            $counts[] = [
                'label' => trans("app.email_delivery.statuses.{$status}"),
                'value' => (clone $query)->where('status', $status)->count(),
                'filter' => ['status' => $status],
                'active' => $active === $status,
            ];
        }

        return $counts;
    }

    /**
     * @param  array<int, array{label:string,value:int,filter:array<string, string>,active:bool}>  $counts
     * @return array{total:int,accepted:int,failed:int,processing:int,acceptance_rate:float|int}
     */
    private function insights(array $counts): array
    {
        $values = collect($counts)->mapWithKeys(
            fn (array $count): array => [$count['filter']['status'] => $count['value']],
        );
        $total = (int) $values->get('all', 0);
        $accepted = (int) $values->get('accepted', 0);

        return [
            'total' => $total,
            'accepted' => $accepted,
            'failed' => (int) $values->get('failed', 0),
            'processing' => (int) $values->get('processing', 0),
            'acceptance_rate' => $total > 0 ? round(($accepted / $total) * 100, 1) : 0,
        ];
    }
}
