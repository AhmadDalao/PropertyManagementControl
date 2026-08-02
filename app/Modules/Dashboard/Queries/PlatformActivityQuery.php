<?php

namespace App\Modules\Dashboard\Queries;

use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Audit\Support\AuditSubjectRegistry;
use App\Modules\Dashboard\Presenters\PlatformActivityPresenter;
use Spatie\Activitylog\Models\Activity;

final readonly class PlatformActivityQuery
{
    private const int RESULT_LIMIT = 8;

    private const int QUERY_LIMIT = 40;

    /** @var list<string> */
    private const array SUBJECTS = [
        'portfolio',
        'user',
        'asset',
        'tenant_profile',
        'lease',
        'payment',
        'maintenance_request',
        'maintenance_vendor',
        'maintenance_work_order',
        'expense_entry',
        'document',
        'cms_page',
        'cms_section',
        'navigation_item',
    ];

    public function __construct(
        private AuditSubjectRegistry $subjects,
        private PlatformActivityPresenter $presenter,
    ) {}

    /** @return list<array<string, mixed>> */
    public function forUser(User $actor): array
    {
        if (! $actor->hasRole('superadmin')) {
            return [];
        }

        $activities = Activity::query()
            ->with(['causer', 'subject'])
            ->whereIn('subject_type', $this->subjectTypes())
            ->latest()
            ->limit(self::QUERY_LIMIT)
            ->get()
            ->unique(fn (Activity $activity): string => implode(':', [
                $activity->subject_type,
                $activity->subject_id,
                $activity->event,
            ]))
            ->values();
        $portfolioIds = $activities
            ->map(fn (Activity $activity): ?int => $this->presenter->portfolioId($activity->subject))
            ->filter()
            ->unique()
            ->values();
        $portfolios = Portfolio::query()
            ->whereIn('id', $portfolioIds)
            ->get()
            ->keyBy('id');

        $rows = $activities
            ->map(fn (Activity $activity): ?array => $this->presenter->present(
                $activity,
                $portfolios,
            ))
            ->filter()
            ->take(self::RESULT_LIMIT)
            ->values()
            ->all();

        return array_values($rows);
    }

    /** @return list<string> */
    private function subjectTypes(): array
    {
        return array_values(array_unique(array_merge(...array_map(
            fn (string $alias): array => $this->subjects->typeValues($alias),
            self::SUBJECTS,
        ))));
    }
}
