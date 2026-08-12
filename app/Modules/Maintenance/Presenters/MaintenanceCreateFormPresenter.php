<?php

namespace App\Modules\Maintenance\Presenters;

use App\Models\User;
use App\Modules\Maintenance\Queries\MaintenanceFormOptionsQuery;
use App\Modules\Maintenance\Support\MaintenanceAccess;
use App\Modules\Maintenance\Support\MaintenanceOptions;
use App\Modules\Shared\ResourcePresenter;

class MaintenanceCreateFormPresenter
{
    public function __construct(
        private readonly MaintenanceAccess $access,
        private readonly MaintenanceFormOptionsQuery $options,
        private readonly MaintenanceFormOptionPresenter $presentOptions,
        private readonly ResourcePresenter $resources,
    ) {}

    /**
     * @param  array{portfolio_id?:mixed,asset_id?:mixed,tenant_profile_id?:mixed}  $defaults
     * @return array<string, mixed>
     */
    public function present(User $actor, array $defaults): array
    {
        return $actor->hasRole('tenant')
            ? $this->tenant($actor, $defaults)
            : $this->manager($actor, $defaults);
    }

    /**
     * @param  array{asset_id?:mixed}  $defaults
     * @return array<string, mixed>
     */
    private function tenant(User $actor, array $defaults): array
    {
        $assets = $this->presentOptions->assets($this->options->tenantAssets($actor));

        return [
            'layout' => 'maintenance',
            'title' => trans('app.maintenance.submit_request'),
            'description' => trans('app.maintenance.submit_request_help'),
            'backHref' => route('maintenance-requests.index'),
            'backLabel' => trans('app.maintenance.my_requests'),
            'action' => route('maintenance-requests.store'),
            'method' => 'post',
            'submitLabel' => trans('app.maintenance.submit_request'),
            'fields' => $this->sections([
                ['name' => 'asset_id', 'label' => trans('app.maintenance.rented_asset'), 'type' => 'select', 'required' => true, 'options' => $assets],
                ['name' => 'category', 'label' => trans('app.maintenance.category'), 'type' => 'select', 'options' => $this->presentOptions->values(MaintenanceOptions::CATEGORIES)],
                ['name' => 'priority', 'label' => trans('app.maintenance.priority'), 'type' => 'select', 'options' => $this->presentOptions->values(MaintenanceOptions::PRIORITIES)],
                ['name' => 'title', 'label' => trans('app.maintenance.issue_title'), 'required' => true],
                ['name' => 'description', 'label' => trans('app.maintenance.issue_description'), 'type' => 'textarea', 'required' => true],
                $this->photoField(),
            ]),
            'initialValues' => [
                'asset_id' => (string) ($defaults['asset_id'] ?? ($assets[0]['value'] ?? '')),
                'category' => 'general',
                'priority' => 'medium',
                'title' => '',
                'description' => '',
                'photos' => [],
            ],
        ];
    }

    /**
     * @param  array{portfolio_id?:mixed,asset_id?:mixed,tenant_profile_id?:mixed}  $defaults
     * @return array<string, mixed>
     */
    private function manager(User $actor, array $defaults): array
    {
        $this->access->ensureManager($actor);
        $portfolios = $this->options->portfolios($actor);
        $assets = $this->presentOptions->assets($this->options->managerAssets($actor));
        $tenants = $this->presentOptions->tenants($this->options->managerTenants($actor));
        $portfolioId = (int) ($defaults['portfolio_id'] ?? $actor->portfolio_id ?? ($portfolios[0]['id'] ?? 0));
        $fields = [];

        if ($actor->hasRole('superadmin')) {
            $fields[] = [
                'name' => 'portfolio_id',
                'label' => trans('app.maintenance.portfolio'),
                'type' => 'select',
                'options' => $this->presentOptions->portfolios($portfolios),
            ];
        }

        return [
            'layout' => 'maintenance',
            'title' => trans('app.maintenance.create_request'),
            'description' => trans('app.maintenance.create_request_help'),
            'backHref' => route('maintenance-requests.index'),
            'backLabel' => trans('app.maintenance.queue_title'),
            'action' => route('maintenance-requests.store'),
            'method' => 'post',
            'submitLabel' => trans('app.maintenance.create_request'),
            'fields' => $this->sections([
                ...$fields,
                ['name' => 'asset_id', 'label' => trans('app.maintenance.asset'), 'type' => 'select', 'required' => true, 'options' => $assets],
                ['name' => 'tenant_profile_id', 'label' => trans('app.maintenance.tenant'), 'type' => 'select', 'required' => true, 'options' => $tenants],
                ['name' => 'assigned_to_user_id', 'label' => trans('app.maintenance.assignee'), 'type' => 'select', 'options' => $this->presentOptions->managers($this->options->managers($actor))],
                ['name' => 'category', 'label' => trans('app.maintenance.category'), 'type' => 'select', 'options' => $this->presentOptions->values(MaintenanceOptions::CATEGORIES)],
                ['name' => 'priority', 'label' => trans('app.maintenance.priority'), 'type' => 'select', 'options' => $this->presentOptions->values(MaintenanceOptions::PRIORITIES)],
                ['name' => 'status', 'label' => trans('app.maintenance.status'), 'type' => 'select', 'options' => $this->presentOptions->values(MaintenanceOptions::STATUSES)],
                ['name' => 'title', 'label' => trans('app.maintenance.issue_title'), 'required' => true],
                ['name' => 'description', 'label' => trans('app.maintenance.issue_description'), 'type' => 'textarea', 'required' => true],
                [
                    'name' => 'resolution_summary',
                    'label' => trans('app.maintenance.resolution_summary'),
                    'type' => 'textarea',
                    'help' => trans('app.maintenance.resolution_summary_help'),
                ],
                ['name' => 'internal_notes', 'label' => trans('app.maintenance.internal_notes'), 'type' => 'textarea'],
                $this->photoField(),
            ]),
            'initialValues' => [
                'portfolio_id' => (string) $portfolioId,
                'asset_id' => (string) ($defaults['asset_id'] ?? ($assets[0]['value'] ?? '')),
                'tenant_profile_id' => (string) ($defaults['tenant_profile_id'] ?? ($tenants[0]['value'] ?? '')),
                'assigned_to_user_id' => '',
                'category' => 'general',
                'priority' => 'medium',
                'status' => 'open',
                'title' => '',
                'description' => '',
                'resolution_summary' => '',
                'internal_notes' => '',
                'photos' => [],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function photoField(): array
    {
        return [
            'name' => 'photos',
            'label' => trans('app.maintenance.photos'),
            'type' => 'file',
            'multiple' => true,
            'accept' => '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp',
            'help' => trans('app.maintenance.photos_help'),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<int, array<string, mixed>>
     */
    private function sections(array $fields): array
    {
        return $this->resources->sectionFields($fields, [
            trans('app.maintenance.request_context_section') => [
                'description' => trans('app.maintenance.request_context_section_help'),
                'fields' => ['portfolio_id', 'asset_id', 'tenant_profile_id', 'assigned_to_user_id'],
            ],
            trans('app.maintenance.classification_section') => [
                'description' => trans('app.maintenance.classification_section_help'),
                'fields' => ['category', 'priority', 'status'],
            ],
            trans('app.maintenance.issue_section') => [
                'description' => trans('app.maintenance.issue_section_help'),
                'fields' => ['title', 'description'],
            ],
            trans('app.maintenance.evidence_section') => [
                'description' => trans('app.maintenance.evidence_section_help'),
                'fields' => ['photos'],
            ],
            trans('app.maintenance.internal_section') => [
                'description' => trans('app.maintenance.internal_section_help'),
                'fields' => ['resolution_summary', 'internal_notes'],
            ],
        ]);
    }
}
