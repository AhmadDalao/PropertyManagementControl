<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetStakeholder;
use App\Models\CmsPage;
use App\Models\CmsPageSection;
use App\Models\CmsSection;
use App\Models\Document;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceUpdate;
use App\Models\MediaFile;
use App\Models\NavigationItem;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Portfolio;
use App\Models\TenantProfile;
use App\Models\User;
use App\Services\XlsxWorkbook;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        $filters = $this->auditFilters($request);
        $baseQuery = $this->scopedActivityQuery($actor, $filters);
        $activities = (clone $baseQuery)->with(['causer', 'subject']);

        $this->applyAuditFilters($activities, $filters);

        $paginator = $this->paginateTable($activities, $request, $filters, [
            'created_at',
            'event',
            'description',
        ]);

        return Inertia::render('admin/audit/index', [
            'activities' => $paginator->through(fn (Activity $activity) => $this->activityPayload($activity)),
            'filters' => $filters,
            'counts' => $this->activityCounts($baseQuery, $filters),
            'portfolioOptions' => $this->portfolioOptions($actor),
            'subjectTypeOptions' => $this->subjectTypeOptions($actor),
            'causerOptions' => $this->causerOptions($actor),
        ]);
    }

    public function export(Request $request, XlsxWorkbook $workbook): BinaryFileResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        $filters = $this->auditFilters($request);
        $query = $this->scopedActivityQuery($actor, $filters)->with(['causer', 'subject']);
        $this->applyAuditFilters($query, $filters);

        $rows = [['Date', 'Event', 'Subject Type', 'Subject', 'Causer', 'Description', 'Changed Fields']];

        $query->orderByDesc('id')->chunk(500, function ($activities) use (&$rows): void {
            foreach ($activities as $activity) {
                $payload = $this->activityPayload($activity);
                $rows[] = [
                    $payload['created_at'],
                    $payload['event'],
                    $payload['subject_type_label'],
                    $payload['subject_label'],
                    $payload['causer_label'],
                    $payload['description'],
                    implode(', ', $payload['changed_keys']),
                ];
            }
        });

        $path = $workbook->create($rows, 'Audit History');

        return response()->download($path, 'audit-log-'.now()->format('Ymd-His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * @return array<string, mixed>
     */
    private function auditFilters(Request $request): array
    {
        return $this->tableFilters($request, [
            'event' => 'all',
            'subject_type' => 'all',
            'causer_id' => 'all',
            'date_from' => '',
            'date_to' => '',
        ]);
    }

    private function scopedActivityQuery(User $actor, array $filters): Builder
    {
        $query = Activity::query();
        $portfolioId = $actor->hasRole('superadmin')
            ? ($filters['portfolio_id'] ?? null)
            : $actor->portfolio_id;

        if ($portfolioId) {
            $this->applyPortfolioSubjectScope($query, (int) $portfolioId);
        }

        return $query;
    }

    private function applyAuditFilters(Builder $query, array $filters): void
    {
        $this->applyExactFilter($query, $filters, 'event');
        $this->applyDateRange($query, $filters, 'created_at');

        if (($filters['subject_type'] ?? 'all') !== 'all') {
            $query->whereIn('subject_type', $this->typeValues((string) $filters['subject_type']));
        }

        if (($filters['causer_id'] ?? 'all') !== 'all' && $filters['causer_id'] !== null) {
            $query
                ->whereIn('causer_type', $this->typeValues('user'))
                ->where('causer_id', (int) $filters['causer_id']);
        }

        $this->applySearch($query, $filters['search'], [
            'description',
            'event',
            'log_name',
            fn (Builder $query, string $search) => ctype_digit($search)
                ? $query->orWhere('subject_id', (int) $search)->orWhere('causer_id', (int) $search)
                : null,
        ]);
    }

    private function applyPortfolioSubjectScope(Builder $query, int $portfolioId): void
    {
        $query->where(function (Builder $query) use ($portfolioId): void {
            $this->orSubjectIds($query, 'portfolio', Portfolio::query()->whereKey($portfolioId)->select('id'));
            $this->orSubjectIds($query, 'user', User::query()->where('portfolio_id', $portfolioId)->select('id'));
            $this->orSubjectIds($query, 'asset', Asset::query()->where('portfolio_id', $portfolioId)->select('id'));
            $this->orSubjectIds($query, 'asset_stakeholder', AssetStakeholder::query()->where('portfolio_id', $portfolioId)->select('id'));
            $this->orSubjectIds($query, 'tenant_profile', TenantProfile::query()->where('portfolio_id', $portfolioId)->select('id'));
            $this->orSubjectIds($query, 'lease', Lease::query()->where('portfolio_id', $portfolioId)->select('id'));
            $this->orSubjectIds(
                $query,
                'lease_installment',
                LeaseInstallment::query()
                    ->whereIn('lease_id', Lease::query()->where('portfolio_id', $portfolioId)->select('id'))
                    ->select('id')
            );
            $this->orSubjectIds($query, 'payment', Payment::query()->where('portfolio_id', $portfolioId)->select('id'));
            $this->orSubjectIds(
                $query,
                'payment_allocation',
                PaymentAllocation::query()
                    ->whereIn('payment_id', Payment::query()->where('portfolio_id', $portfolioId)->select('id'))
                    ->select('id')
            );
            $this->orSubjectIds($query, 'maintenance_request', MaintenanceRequest::query()->where('portfolio_id', $portfolioId)->select('id'));
            $this->orSubjectIds(
                $query,
                'maintenance_update',
                MaintenanceUpdate::query()
                    ->whereIn('maintenance_request_id', MaintenanceRequest::query()->where('portfolio_id', $portfolioId)->select('id'))
                    ->select('id')
            );
            $this->orSubjectIds($query, 'expense_entry', ExpenseEntry::query()->where('portfolio_id', $portfolioId)->select('id'));
            $this->orSubjectIds($query, 'document', Document::query()->where('portfolio_id', $portfolioId)->select('id'));
            $this->orSubjectIds($query, 'media_file', MediaFile::query()->where('portfolio_id', $portfolioId)->select('id'));
        });
    }

    private function orSubjectIds(Builder $query, string $alias, Builder $ids): void
    {
        $query->orWhere(function (Builder $query) use ($alias, $ids): void {
            $query
                ->whereIn('subject_type', $this->typeValues($alias))
                ->whereIn('subject_id', $ids);
        });
    }

    /**
     * @return array<int, array{label:string,value:int,filter:array<string, string>,active:bool}>
     */
    private function activityCounts(Builder $baseQuery, array $filters): array
    {
        $activeEvent = (string) ($filters['event'] ?? 'all');
        $counts = [[
            'label' => 'All',
            'value' => (clone $baseQuery)->count(),
            'filter' => ['event' => 'all'],
            'active' => $activeEvent === 'all',
        ]];

        foreach (['created', 'updated', 'deleted'] as $event) {
            $counts[] = [
                'label' => str($event)->title()->toString(),
                'value' => (clone $baseQuery)->where('event', $event)->count(),
                'filter' => ['event' => $event],
                'active' => $activeEvent === $event,
            ];
        }

        return $counts;
    }

    /**
     * @return array<string, mixed>
     */
    private function activityPayload(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'event' => $activity->event ?: 'activity',
            'description' => $activity->description,
            'subject_type' => $activity->subject_type,
            'subject_type_label' => $this->subjectTypeLabel($activity->subject_type),
            'subject_label' => $this->modelLabel($activity->subject, $activity->subject_id, $activity->subject_type),
            'causer_label' => $this->modelLabel($activity->causer, $activity->causer_id, $activity->causer_type, 'System'),
            'changed_keys' => $this->changedKeys($activity),
            'created_at' => $activity->created_at?->toDateTimeString(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function changedKeys(Activity $activity): array
    {
        $changes = $activity->attribute_changes instanceof Collection
            ? $activity->attribute_changes->toArray()
            : (array) $activity->attribute_changes;
        $properties = $activity->properties instanceof Collection
            ? $activity->properties->toArray()
            : (array) $activity->properties;
        $attributes = $changes['attributes'] ?? $properties['attributes'] ?? [];

        return collect(array_keys((array) $attributes))
            ->reject(fn (string $key) => in_array($key, ['password', 'remember_token'], true))
            ->values()
            ->all();
    }

    private function modelLabel(?Model $model, ?int $id, ?string $type, string $fallback = 'Unknown'): string
    {
        if (! $model) {
            return $type && $id ? $this->subjectTypeLabel($type).' #'.$id : $fallback;
        }

        foreach (['name', 'title_en', 'code', 'reference', 'email', 'original_name', 'title'] as $attribute) {
            if (! empty($model->{$attribute})) {
                return (string) $model->{$attribute};
            }
        }

        return class_basename($model).' #'.$model->getKey();
    }

    /**
     * @return array<int, array{label:string,value:string}>
     */
    private function subjectTypeOptions(User $actor): array
    {
        $allowed = $actor->hasRole('superadmin')
            ? array_keys($this->subjectTypes())
            : [
                'portfolio',
                'user',
                'asset',
                'asset_stakeholder',
                'tenant_profile',
                'lease',
                'lease_installment',
                'payment',
                'payment_allocation',
                'maintenance_request',
                'maintenance_update',
                'expense_entry',
                'document',
                'media_file',
            ];

        return collect($allowed)
            ->map(fn (string $alias) => [
                'label' => $this->subjectTypeLabel($alias),
                'value' => $alias,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    private function causerOptions(User $actor): array
    {
        return $this->scopeByPortfolio(User::query()->orderBy('name'), $actor)
            ->limit(250)
            ->get(['id', 'name'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->all();
    }

    private function subjectTypeLabel(?string $type): string
    {
        if (! $type) {
            return 'System';
        }

        $aliases = array_flip($this->subjectTypes());
        $alias = $aliases[$type] ?? $type;

        return str($alias)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    /**
     * @return array<string, class-string<Model>>
     */
    private function subjectTypes(): array
    {
        return [
            'portfolio' => Portfolio::class,
            'user' => User::class,
            'asset' => Asset::class,
            'asset_stakeholder' => AssetStakeholder::class,
            'tenant_profile' => TenantProfile::class,
            'lease' => Lease::class,
            'lease_installment' => LeaseInstallment::class,
            'payment' => Payment::class,
            'payment_allocation' => PaymentAllocation::class,
            'maintenance_request' => MaintenanceRequest::class,
            'maintenance_update' => MaintenanceUpdate::class,
            'expense_entry' => ExpenseEntry::class,
            'document' => Document::class,
            'media_file' => MediaFile::class,
            'cms_page' => CmsPage::class,
            'cms_section' => CmsSection::class,
            'cms_page_section' => CmsPageSection::class,
            'navigation_item' => NavigationItem::class,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function typeValues(string $alias): array
    {
        $class = $this->subjectTypes()[$alias] ?? null;

        return $class ? [$alias, $class] : [$alias];
    }
}
