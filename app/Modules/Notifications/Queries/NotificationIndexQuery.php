<?php

namespace App\Modules\Notifications\Queries;

use App\Models\User;
use App\Modules\Notifications\Presenters\NotificationItemPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;

final readonly class NotificationIndexQuery
{
    /** @var list<string> */
    private const TYPES = ['maintenance_request', 'payment', 'lease', 'document'];

    public function __construct(private NotificationItemPresenter $items) {}

    /**
     * @param  array{status:string,type:string,search:string}  $filters
     * @return array<string, mixed>
     */
    public function handle(User $actor, array $filters): array
    {
        $context = $this->search($actor->notifications()->getQuery(), $filters['search']);
        $base = $this->type(clone $context, $filters['type']);
        $counts = [
            'all' => (clone $base)->count(),
            'unread' => (clone $base)->whereNull('read_at')->count(),
            'read' => (clone $base)->whereNotNull('read_at')->count(),
        ];
        $typeCounts = collect(self::TYPES)
            ->mapWithKeys(fn (string $type): array => [
                $type => $this->type(clone $context, $type)->count(),
            ])
            ->all();
        $notifications = $base
            ->when($filters['status'] === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($filters['status'] === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(
                fn (DatabaseNotification $notification): array => $this->items->present($notification),
            );

        return [
            'filters' => $filters,
            'counts' => $counts,
            'typeCounts' => ['all' => array_sum($typeCounts), ...$typeCounts],
            'notificationItems' => $notifications,
        ];
    }

    /**
     * @param  Builder<DatabaseNotification>  $query
     * @return Builder<DatabaseNotification>
     */
    private function type(Builder $query, string $type): Builder
    {
        return $query->when(
            $type !== 'all',
            fn ($records) => $records->where('data->resource_type', $type),
        );
    }

    /**
     * @param  Builder<DatabaseNotification>  $query
     * @return Builder<DatabaseNotification>
     */
    private function search(Builder $query, string $search): Builder
    {
        return $query->when($search !== '', function ($records) use ($search): void {
            $term = '%'.addcslashes($search, '%_\\').'%';
            $records->where(function (Builder $copy) use ($term): void {
                foreach (['title_en', 'title_ar', 'body_en', 'body_ar', 'event'] as $field) {
                    $copy->orWhere("data->{$field}", 'like', $term);
                }
            });
        });
    }
}
