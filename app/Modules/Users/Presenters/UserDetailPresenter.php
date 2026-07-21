<?php

namespace App\Modules\Users\Presenters;

use App\Models\AssetStakeholder;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Users\Support\UserAccess;
use Illuminate\Support\Collection;

class UserDetailPresenter
{
    public function __construct(
        private readonly UserAccess $access,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $target, User $actor): array
    {
        $this->access->ensureCanManage($actor, $target);
        $target->loadMissing(['portfolio', 'roles', 'tenantProfile']);

        $stakeholderQuery = AssetStakeholder::query()->where('user_id', $target->id);
        $stakeholders = (clone $stakeholderQuery)
            ->with('asset:id,portfolio_id,title_en,title_ar,code,status')
            ->latest()
            ->limit(8)
            ->get();
        $maintenance = $target->assignedMaintenanceRequests()
            ->with('asset:id,portfolio_id,title_en,title_ar,code')
            ->latest('requested_at')
            ->limit(8)
            ->get();
        $documents = $target->uploadedDocuments()
            ->latest()
            ->limit(8)
            ->get();
        $roles = $target->roles
            ->pluck('name')
            ->map(fn (string $role): string => trans("app.roles.{$role}"))
            ->join(' / ');
        $portfolioName = $this->resources->localized(
            $target->portfolio?->name_en,
            $target->portfolio?->name_ar,
        );
        $ownedPortfolioCount = $target->portfoliosOwned()->count();
        $activeLeaseCount = $target->tenantProfile?->leases()->where('status', 'active')->count() ?? 0;
        $openMaintenanceCount = $target->assignedMaintenanceRequests()
            ->whereIn('status', ['open', 'in_progress'])
            ->count();
        $actions = [
            [
                'label' => trans('app.users.edit_user'),
                'href' => route('users.edit', $target),
                'variant' => 'primary',
            ],
        ];

        if ($target->tenantProfile) {
            $actions[] = [
                'label' => trans('app.users.open_tenant_profile'),
                'href' => route('tenants.show', $target->tenantProfile),
                'variant' => 'secondary',
            ];
        }

        if ($target->status !== 'suspended') {
            $actions[] = [
                'label' => trans('app.users.suspend_user'),
                'href' => route('users.destroy', $target),
                'method' => 'delete',
                'variant' => 'danger',
                'confirm' => trans('app.users.archive_confirm', ['name' => $target->name]),
            ];
        }

        return [
            'header' => [
                'eyebrow' => trans('app.users.detail_eyebrow'),
                'title' => $target->name,
                'description' => trans('app.users.detail_description', [
                    'role' => $roles,
                    'status' => trans("app.status.{$target->status}"),
                ]),
                'backHref' => route('users.index'),
                'backLabel' => trans('app.users.all_users'),
                'actions' => $actions,
            ],
            'decisionCards' => [
                [
                    'title' => trans('app.users.portal_access'),
                    'value' => trans("app.status.{$target->status}"),
                    'detail' => $target->email,
                    'tone' => $target->status === 'active' ? 'teal' : 'danger',
                    'icon' => 'bi-person-lock',
                ],
                [
                    'title' => trans('app.users.role'),
                    'value' => $roles,
                    'detail' => $portfolioName ?: trans('app.users.global_account'),
                    'tone' => 'primary',
                    'icon' => 'bi-shield-check',
                ],
                [
                    'title' => trans('app.users.asset_assignments'),
                    'value' => (clone $stakeholderQuery)->whereNull('ends_on')->count(),
                    'detail' => trans('app.users.ownership_summary', ['count' => $ownedPortfolioCount]),
                    'tone' => 'muted',
                    'icon' => 'bi-buildings',
                ],
                [
                    'title' => trans('app.users.open_workload'),
                    'value' => $openMaintenanceCount,
                    'detail' => trans('app.users.active_lease_summary', ['count' => $activeLeaseCount]),
                    'tone' => $openMaintenanceCount > 0 ? 'danger' : 'muted',
                    'icon' => 'bi-tools',
                ],
            ],
            'stats' => $this->resources->detailItems([
                ['label' => trans('app.users.status'), 'value' => trans("app.status.{$target->status}"), 'tone' => $target->status === 'active' ? 'teal' : 'danger'],
                ['label' => trans('app.users.role'), 'value' => $roles, 'tone' => 'primary'],
                ['label' => trans('app.users.recorded_payments'), 'value' => $target->recordedPayments()->count()],
                ['label' => trans('app.users.open_workload'), 'value' => $openMaintenanceCount, 'tone' => $openMaintenanceCount > 0 ? 'danger' : 'muted'],
            ]),
            'sections' => [
                [
                    'title' => trans('app.users.account_section'),
                    'description' => trans('app.users.account_section_help'),
                    'items' => $this->resources->detailItems([
                        ['label' => trans('app.users.email'), 'value' => $target->email],
                        ['label' => trans('app.users.phone'), 'value' => $target->phone],
                        ['label' => trans('app.users.preferred_language'), 'value' => trans('app.users.'.($target->preferred_locale === 'ar' ? 'arabic' : 'english'))],
                        ['label' => trans('app.users.portfolio'), 'value' => $portfolioName, 'href' => $target->portfolio ? route('portfolios.show', $target->portfolio) : null],
                        ['label' => trans('app.users.role'), 'value' => $roles],
                    ]),
                ],
                [
                    'title' => trans('app.users.security_section'),
                    'description' => trans('app.users.security_section_help'),
                    'items' => $this->resources->detailItems([
                        ['label' => trans('app.users.temporary_password_state'), 'value' => trans('app.users.'.($target->force_password_reset ? 'yes' : 'no'))],
                        ['label' => trans('app.users.last_login'), 'value' => $target->last_login_at?->toDateTimeString()],
                        ['label' => trans('app.users.created_at'), 'value' => $target->created_at?->toDateTimeString()],
                        ['label' => trans('app.users.updated_at'), 'value' => $target->updated_at?->toDateTimeString()],
                    ]),
                ],
            ],
            'related' => [
                $this->stakeholderTable($stakeholders),
                $this->maintenanceTable($maintenance),
            ],
            'documents' => $this->resources->documentStrip($documents),
            'timeline' => $this->resources->activityTimeline($target),
        ];
    }

    /** @param Collection<int, AssetStakeholder> $stakeholders */
    private function stakeholderTable(Collection $stakeholders): array
    {
        return [
            'title' => trans('app.users.assets_title'),
            'description' => trans('app.users.assets_help'),
            'columns' => [
                trans('app.users.asset'),
                trans('app.users.relationship'),
                trans('app.users.primary'),
                trans('app.users.status'),
            ],
            'rows' => $stakeholders->map(fn (AssetStakeholder $stakeholder): array => [
                trans('app.users.asset') => $stakeholder->asset
                    ? [
                        'label' => $this->resources->localized($stakeholder->asset->title_en, $stakeholder->asset->title_ar) ?? '-',
                        'href' => route('assets.show', $stakeholder->asset),
                    ]
                    : '-',
                trans('app.users.relationship') => trans("app.users.relationship_{$stakeholder->relationship_type}"),
                trans('app.users.primary') => trans('app.users.'.($stakeholder->is_primary ? 'yes' : 'no')),
                trans('app.users.status') => trans('app.status.'.($stakeholder->ends_on ? 'inactive' : 'active')),
            ])->all(),
            'emptyText' => trans('app.users.no_assets'),
        ];
    }

    /** @param Collection<int, MaintenanceRequest> $requests */
    private function maintenanceTable(Collection $requests): array
    {
        return [
            'title' => trans('app.users.maintenance_title'),
            'description' => trans('app.users.maintenance_help'),
            'columns' => [
                trans('app.users.request'),
                trans('app.users.asset'),
                trans('app.users.status'),
                trans('app.users.priority'),
            ],
            'rows' => $requests->map(fn (MaintenanceRequest $request): array => [
                trans('app.users.request') => [
                    'label' => '#'.$request->id.' '.$request->title,
                    'href' => route('maintenance-requests.show', $request),
                ],
                trans('app.users.asset') => $request->asset
                    ? [
                        'label' => $this->resources->localized($request->asset->title_en, $request->asset->title_ar) ?? '-',
                        'href' => route('assets.show', $request->asset),
                    ]
                    : '-',
                trans('app.users.status') => trans("app.status.{$request->status}"),
                trans('app.users.priority') => trans("app.status.{$request->priority}"),
            ])->all(),
            'emptyText' => trans('app.users.no_maintenance'),
        ];
    }
}
