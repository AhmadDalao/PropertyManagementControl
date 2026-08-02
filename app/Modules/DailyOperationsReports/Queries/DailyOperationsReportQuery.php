<?php

namespace App\Modules\DailyOperationsReports\Queries;

use App\Models\DailyOperationsReportRun;
use App\Models\User;
use App\Modules\DailyOperationsReports\Requests\DailyOperationsReportIndexRequest;
use App\Modules\DailyOperationsReports\Support\DailyOperationsReportAccess;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Throwable;

final readonly class DailyOperationsReportQuery
{
    public function __construct(
        private DailyOperationsReportAccess $access,
        private PortfolioScope $portfolios,
    ) {}

    /** @return array<string, mixed> */
    public function index(
        DailyOperationsReportIndexRequest $request,
        User $actor,
    ): array {
        $this->access->ensureArchiveActor($actor);
        $filters = $request->filters();
        $filters['portfolio_id'] = $this->access->portfolioId($actor, $filters['portfolio_id']);
        $query = $this->scoped($actor)
            ->with(['portfolio:id,name_en,name_ar', 'initiatedBy:id,name'])
            ->latest('report_date')
            ->latest('id');

        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if ($filters['portfolio_id']) {
            $query->where('portfolio_id', $filters['portfolio_id']);
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('report_date', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('report_date', '<=', $filters['date_to']);
        }

        /** @var LengthAwarePaginator<int, DailyOperationsReportRun> $runs */
        $runs = $query->paginate(12)->withQueryString();
        $runs->through(fn (DailyOperationsReportRun $run): array => $this->record($actor, $run));
        $base = $this->scoped($actor);
        $latest = (clone $base)->where('status', 'completed')->latest('completed_at')->first();

        return [
            'reports' => $runs,
            'filters' => $filters,
            'summary' => [
                'completed' => (clone $base)->where('status', 'completed')->count(),
                'failed' => (clone $base)->where('status', 'failed')->count(),
                'active' => (clone $base)->whereIn('status', ['queued', 'running'])->count(),
                'items' => (int) (clone $base)->where('status', 'completed')->sum('item_count'),
                'latest_completed_at' => $latest?->completed_at?->toIso8601String(),
                'retention_days' => max(1, (int) config('operations.daily_report_retention_days', 90)),
                'schedule_time' => (string) config('operations.daily_report_schedule', '06:00'),
            ],
            'portfolioOptions' => $this->portfolios->options($actor),
            'statusOptions' => collect(['all', 'queued', 'running', 'completed', 'failed', 'pruned'])
                ->map(fn (string $value): array => [
                    'value' => $value,
                    'label' => trans("app.daily_reports.statuses.{$value}"),
                ])
                ->all(),
            'canSelectGlobal' => $actor->hasRole('superadmin'),
        ];
    }

    /** @return array<string, mixed> */
    public function show(User $actor, DailyOperationsReportRun $run): array
    {
        $this->access->ensureCanAccess($actor, $run);
        $run->load(['portfolio:id,name_en,name_ar', 'initiatedBy:id,name']);

        return ['report' => $this->record($actor, $run)];
    }

    /** @return Builder<DailyOperationsReportRun> */
    private function scoped(User $actor): Builder
    {
        $query = DailyOperationsReportRun::query();

        if (! $actor->hasRole('superadmin')) {
            $query->where('portfolio_id', $actor->portfolio_id);
        }

        return $query;
    }

    /** @return array<string, mixed> */
    private function record(User $actor, DailyOperationsReportRun $run): array
    {
        $files = [];
        $archivedScope = data_get($run->scope_json, '1.value');
        $portfolioName = $run->portfolio
            ? (app()->isLocale('ar')
                ? ($run->portfolio->name_ar ?: $run->portfolio->name_en)
                : ($run->portfolio->name_en ?: $run->portfolio->name_ar))
            : null;

        foreach (['pdf', 'docx', 'xlsx'] as $format) {
            $path = $run->{$format.'_path'};
            $available = false;

            if ($run->status === 'completed' && $path) {
                try {
                    $available = Storage::disk($run->storage_disk)->exists($path);
                } catch (Throwable) {
                    $available = false;
                }
            }

            $files[$format] = [
                'available' => $available,
                'bytes' => $run->{$format.'_bytes'},
                'url' => route('reports.daily-operations.download', [
                    'dailyOperationsReportRun' => $run,
                    'format' => $format,
                ]),
            ];
        }

        return [
            'id' => $run->id,
            'status' => $run->status,
            'status_label' => trans("app.daily_reports.statuses.{$run->status}"),
            'trigger' => $run->trigger,
            'trigger_label' => trans("app.daily_reports.triggers.{$run->trigger}"),
            'report_date' => $run->report_date->toDateString(),
            'portfolio' => $run->portfolio ? [
                'id' => $run->portfolio->id,
                'name' => app()->isLocale('ar')
                    ? ($run->portfolio->name_ar ?: $run->portfolio->name_en)
                    : ($run->portfolio->name_en ?: $run->portfolio->name_ar),
            ] : null,
            'scope_label' => $portfolioName
                ?: (is_string($archivedScope) && $archivedScope !== ''
                    ? $archivedScope
                    : trans('app.daily_reports.global_scope')),
            'initiated_by' => $run->initiatedBy?->name,
            'item_count' => $run->item_count,
            'summary' => $run->summary_json ?? [],
            'scope' => $run->scope_json ?? [],
            'failure_summary' => $run->failure_summary,
            'created_at' => $run->created_at?->toIso8601String(),
            'started_at' => $run->started_at?->toIso8601String(),
            'completed_at' => $run->completed_at?->toIso8601String(),
            'failed_at' => $run->failed_at?->toIso8601String(),
            'files' => $files,
            'show_url' => route('reports.daily-operations.show', $run),
            'action_center_url' => route('action-center.index', array_filter([
                'portfolio_id' => $run->portfolio_id,
            ])),
            'can_prune' => $run->status === 'completed'
                && ($actor->hasRole('superadmin') || $run->portfolio_id === $actor->portfolio_id),
            'prune_url' => route('reports.daily-operations.destroy', $run),
        ];
    }
}
