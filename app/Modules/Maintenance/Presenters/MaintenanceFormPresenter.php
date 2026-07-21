<?php

namespace App\Modules\Maintenance\Presenters;

use App\Models\Asset;
use App\Models\MaintenanceRequest;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Maintenance\Support\MaintenanceAccess;
use App\Modules\Maintenance\Support\MaintenanceOptions;
use App\Modules\Shared\MorphTypes;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\ResourcePresenter;

class MaintenanceFormPresenter
{
    public function __construct(
        private readonly MaintenanceAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly ResourcePresenter $resources,
        private readonly MorphTypes $morphTypes,
    ) {}

    /**
     * @param  array{portfolio_id?:mixed,asset_id?:mixed,tenant_profile_id?:mixed}  $defaults
     * @return array<string, mixed>
     */
    public function present(User $actor, ?MaintenanceRequest $request = null, array $defaults = []): array
    {
        if ($request) {
            return $actor->hasRole('tenant')
                ? $this->tenantCommentForm($request)
                : $this->managerTriageForm($actor, $request);
        }

        return $actor->hasRole('tenant')
            ? $this->tenantCreateForm($actor, $defaults)
            : $this->managerCreateForm($actor, $defaults);
    }

    /** @return array<string, mixed> */
    private function tenantCommentForm(MaintenanceRequest $request): array
    {
        return [
            'title' => 'Add maintenance comment',
            'description' => 'Send a public update to the owner or manager.',
            'backHref' => route('maintenance-requests.show', $request),
            'backLabel' => 'Request detail',
            'action' => route('maintenance-requests.update', $request),
            'method' => 'put',
            'submitLabel' => 'Add comment',
            'fields' => [
                ['name' => 'comment', 'label' => 'Comment', 'type' => 'textarea', 'required' => true],
            ],
            'initialValues' => ['comment' => ''],
        ];
    }

    /** @return array<string, mixed> */
    private function managerTriageForm(User $actor, MaintenanceRequest $request): array
    {
        $this->access->ensureManager($actor);

        return [
            'title' => 'Triage request #'.$request->id,
            'description' => 'Assign, prioritize, change status, and leave a visible or internal update.',
            'backHref' => route('maintenance-requests.show', $request),
            'backLabel' => 'Request detail',
            'action' => route('maintenance-requests.update', $request),
            'method' => 'put',
            'submitLabel' => 'Update request',
            'fields' => [
                ['name' => 'assigned_to_user_id', 'label' => 'Assignee', 'type' => 'select', 'options' => $this->managerOptions($actor)],
                ['name' => 'priority', 'label' => 'Priority', 'type' => 'select', 'options' => $this->resources->fieldOptions(MaintenanceOptions::PRIORITIES)],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $this->resources->fieldOptions(MaintenanceOptions::STATUSES)],
                ['name' => 'internal_notes', 'label' => 'Internal notes', 'type' => 'textarea'],
                ['name' => 'comment', 'label' => 'Update comment', 'type' => 'textarea'],
                ['name' => 'is_public_comment', 'label' => 'Show comment to tenant', 'type' => 'checkbox'],
            ],
            'initialValues' => [
                'assigned_to_user_id' => (string) ($request->assigned_to_user_id ?? ''),
                'priority' => $request->priority,
                'status' => $request->status,
                'internal_notes' => $request->internal_notes ?? '',
                'comment' => '',
                'is_public_comment' => false,
            ],
        ];
    }

    /**
     * @param  array{asset_id?:mixed}  $defaults
     * @return array<string, mixed>
     */
    private function tenantCreateForm(User $actor, array $defaults): array
    {
        $tenant = TenantProfile::query()->where('user_id', $actor->id)->first();
        $assets = $tenant?->leases()
            ->where('status', 'active')
            ->whereIn('leaseable_type', $this->morphTypes->for(new Asset))
            ->with('leaseable')
            ->get()
            ->pluck('leaseable')
            ->filter(fn ($asset) => $asset instanceof Asset)
            ->unique('id')
            ->values() ?? collect();
        $assetOptions = $assets->map(fn (Asset $asset) => $this->option(
            $asset->id,
            $this->resources->localized($asset->title_en, $asset->title_ar).' · '.$asset->code,
        ))->all();

        return [
            'title' => 'Submit maintenance request',
            'description' => 'Tell the owner what broke and where. Keep it clear; photos can be added later through documents.',
            'backHref' => route('maintenance-requests.index'),
            'backLabel' => 'My requests',
            'action' => route('maintenance-requests.store'),
            'method' => 'post',
            'submitLabel' => 'Submit request',
            'fields' => [
                ['name' => 'asset_id', 'label' => 'Rented asset', 'type' => 'select', 'required' => true, 'options' => $assetOptions],
                ['name' => 'category', 'label' => 'Category', 'type' => 'select', 'options' => $this->resources->fieldOptions(MaintenanceOptions::CATEGORIES)],
                ['name' => 'priority', 'label' => 'Priority', 'type' => 'select', 'options' => $this->resources->fieldOptions(MaintenanceOptions::PRIORITIES)],
                ['name' => 'title', 'label' => 'Issue title', 'required' => true],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => true],
            ],
            'initialValues' => [
                'asset_id' => (string) ($defaults['asset_id'] ?? ($assetOptions[0]['value'] ?? '')),
                'category' => 'general',
                'priority' => 'medium',
                'title' => '',
                'description' => '',
            ],
        ];
    }

    /**
     * @param  array{portfolio_id?:mixed,asset_id?:mixed,tenant_profile_id?:mixed}  $defaults
     * @return array<string, mixed>
     */
    private function managerCreateForm(User $actor, array $defaults): array
    {
        $this->access->ensureManager($actor);
        $portfolioOptions = $this->portfolios->options($actor);
        $portfolioId = (int) ($defaults['portfolio_id'] ?? $actor->portfolio_id ?? ($portfolioOptions[0]['id'] ?? 0));
        $assetOptions = $this->portfolios->apply(Asset::query()->orderBy('title_en'), $actor)
            ->get()
            ->map(fn (Asset $asset) => $this->option(
                $asset->id,
                $this->resources->localized($asset->title_en, $asset->title_ar).' · '.$asset->code,
            ))->all();
        $tenantOptions = $this->portfolios->apply(TenantProfile::query()->with('user'), $actor)
            ->get()
            ->map(fn (TenantProfile $tenant) => $this->option(
                $tenant->id,
                $tenant->user->name ?? 'Tenant #'.$tenant->id,
            ))->all();
        $fields = [];

        if ($actor->hasRole('superadmin')) {
            $fields[] = [
                'name' => 'portfolio_id',
                'label' => 'Portfolio',
                'type' => 'select',
                'options' => collect($portfolioOptions)
                    ->map(fn (array $portfolio) => $this->option($portfolio['id'], $portfolio['name']))
                    ->all(),
            ];
        }

        $fields = [
            ...$fields,
            ['name' => 'asset_id', 'label' => 'Asset', 'type' => 'select', 'required' => true, 'options' => $assetOptions],
            ['name' => 'tenant_profile_id', 'label' => 'Tenant', 'type' => 'select', 'required' => true, 'options' => $tenantOptions],
            ['name' => 'assigned_to_user_id', 'label' => 'Assignee', 'type' => 'select', 'options' => $this->managerOptions($actor)],
            ['name' => 'category', 'label' => 'Category', 'type' => 'select', 'options' => $this->resources->fieldOptions(MaintenanceOptions::CATEGORIES)],
            ['name' => 'priority', 'label' => 'Priority', 'type' => 'select', 'options' => $this->resources->fieldOptions(MaintenanceOptions::PRIORITIES)],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $this->resources->fieldOptions(MaintenanceOptions::STATUSES)],
            ['name' => 'title', 'label' => 'Issue title', 'required' => true],
            ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => true],
            ['name' => 'internal_notes', 'label' => 'Internal notes', 'type' => 'textarea'],
        ];

        return [
            'title' => 'Create maintenance request',
            'description' => 'Open an issue, attach it to the right tenant and asset, then triage from the detail page.',
            'backHref' => route('maintenance-requests.index'),
            'backLabel' => 'Maintenance queue',
            'action' => route('maintenance-requests.store'),
            'method' => 'post',
            'submitLabel' => 'Create request',
            'fields' => $fields,
            'initialValues' => [
                'portfolio_id' => (string) $portfolioId,
                'asset_id' => (string) ($defaults['asset_id'] ?? ($assetOptions[0]['value'] ?? '')),
                'tenant_profile_id' => (string) ($defaults['tenant_profile_id'] ?? ($tenantOptions[0]['value'] ?? '')),
                'assigned_to_user_id' => '',
                'category' => 'general',
                'priority' => 'medium',
                'status' => 'open',
                'title' => '',
                'description' => '',
                'internal_notes' => '',
            ],
        ];
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    private function managerOptions(User $actor): array
    {
        return $this->portfolios->apply(
            User::query()
                ->whereHas('roles', fn ($query) => $query->whereIn('name', ['owner', 'property_manager']))
                ->orderBy('name'),
            $actor
        )->get()
            ->map(fn (User $user) => $this->option($user->id, $user->name))
            ->prepend(['value' => '', 'label' => 'Unassigned'])
            ->values()
            ->all();
    }

    /** @return array{value:string,label:string} */
    private function option(int|string $value, string $label): array
    {
        return ['value' => (string) $value, 'label' => $label];
    }
}
