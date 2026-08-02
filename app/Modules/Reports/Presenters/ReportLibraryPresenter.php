<?php

namespace App\Modules\Reports\Presenters;

use App\Models\User;
use App\Modules\Portfolios\Support\PortfolioModules;

final class ReportLibraryPresenter
{
    public function __construct(
        private readonly ReportLibraryScopePresenter $scopes,
    ) {}

    /**
     * @param  array{period:string,date_from:string,date_to:string,portfolio_id:int|null,property_id:int|null}  $filters
     * @param  array<int, array{id:int,name:string}>  $portfolioOptions
     * @param  array<int, array{id:int,portfolio_id:int,name:string}>  $propertyOptions
     * @return array<int, array{key:string,title:string,description:string,cards:array<int, array<string, mixed>>}>
     */
    public function present(
        User $actor,
        array $filters,
        array $portfolioOptions,
        array $propertyOptions,
    ): array {
        $query = array_filter($filters, static fn (mixed $value): bool => $value !== null && $value !== '');
        $scope = $this->scopes->present(
            $actor,
            $filters,
            $portfolioOptions,
            $propertyOptions,
        );
        $currentQuery = array_filter([
            'portfolio_id' => $filters['portfolio_id'],
            'property_id' => $filters['property_id'],
        ], static fn (mixed $value): bool => $value !== null);

        return array_values(array_filter([
            $this->group('owner', [
                $this->card(
                    'owner-statement',
                    'bi-file-earmark-person',
                    'owner_statement',
                    'owner_statement_description',
                    $this->url('reports.statement', $query),
                    [
                        $this->download('PDF', $this->url('reports.statement.pdf', $query)),
                        $this->download('DOCX', $this->url('reports.statement.word', $query)),
                        $this->download('XLSX', $this->url('reports.statement.workbook', $query)),
                    ],
                    'reports',
                    $scope['period'],
                ),
                $filters['property_id'] !== null
                    ? $this->card(
                        'property-operating-report',
                        'bi-building-check',
                        'property_operating_report',
                        'property_operating_report_description',
                        $this->url('reports.properties.show', [
                            'asset' => $filters['property_id'],
                            'date_from' => $filters['date_from'],
                            'date_to' => $filters['date_to'],
                        ]),
                        [
                            $this->download('PDF', $this->url('reports.statement.pdf', $query)),
                            $this->download('DOCX', $this->url('reports.statement.word', $query)),
                            $this->download('XLSX', $this->url('reports.statement.workbook', $query)),
                        ],
                        'reports',
                        $scope['period'],
                    )
                    : $this->card(
                        'portfolio-performance',
                        'bi-graph-up-arrow',
                        'portfolio_performance',
                        'portfolio_performance_description',
                        $this->url('reports.index', [...$query, 'tab' => 'overview']),
                        [$this->download('XLSX', $this->url('reports.export', $query))],
                        'reports',
                        $scope['period'],
                    ),
                $this->card(
                    'rent-roll',
                    'bi-table',
                    'rent_roll',
                    'rent_roll_description',
                    $this->url('reports.rent-roll.index', $currentQuery),
                    [
                        $this->download('PDF', $this->url('reports.rent-roll.pdf', $currentQuery)),
                        $this->download('DOCX', $this->url('reports.rent-roll.word', $currentQuery)),
                        $this->download('XLSX', $this->url('reports.rent-roll.workbook', $currentQuery)),
                    ],
                    'reports',
                    $scope['current'],
                ),
            ], $actor),
            $this->group('finance', [
                $this->resourceCard(
                    'rent-collection',
                    'bi-wallet2',
                    'rent_collection',
                    'rent_collection_description',
                    $this->url('rent-collection.index', $query),
                    $this->url('exports.resource', ['resource' => 'rent-collection', ...$query]),
                    'payments',
                    $scope['period'],
                ),
                $this->resourceCard(
                    'payments',
                    'bi-cash-stack',
                    'payment_register',
                    'payment_register_description',
                    $this->url('payments.index', $query),
                    $this->url('exports.resource', ['resource' => 'payments', ...$query]),
                    'payments',
                    $scope['period'],
                ),
                $this->resourceCard(
                    'expenses',
                    'bi-receipt',
                    'expense_register',
                    'expense_register_description',
                    $this->url('expenses.index', $query),
                    $this->url('exports.resource', ['resource' => 'expenses', ...$query]),
                    'expenses',
                    $scope['period'],
                ),
            ], $actor),
            $this->group('operations', [
                $this->resourceCard(
                    'lease-renewals',
                    'bi-calendar2-check',
                    'lease_expiry',
                    'lease_expiry_description',
                    $this->url('lease-renewals.index', $query),
                    $this->url('exports.resource', ['resource' => 'lease-renewals', ...$query]),
                    'leases',
                    $scope['period'],
                ),
                $this->resourceCard(
                    'lease-move-outs',
                    'bi-box-arrow-right',
                    'move_outs',
                    'move_outs_description',
                    $this->url('lease-move-outs.index', $query),
                    $this->url('exports.resource', ['resource' => 'lease-move-outs', ...$query]),
                    'leases',
                    $scope['period'],
                ),
                $this->resourceCard(
                    'maintenance',
                    'bi-tools',
                    'maintenance_register',
                    'maintenance_register_description',
                    $this->url('maintenance-requests.index', $query),
                    $this->url('exports.resource', ['resource' => 'maintenance-requests', ...$query]),
                    'maintenance',
                    $scope['period'],
                ),
                $this->resourceCard(
                    'occupancy',
                    'bi-buildings',
                    'occupancy_register',
                    'occupancy_register_description',
                    $this->url('property-explorer.index', [
                        'property_id' => $filters['property_id'],
                    ]),
                    $this->url('exports.resource', ['resource' => 'assets', ...$query]),
                    'assets',
                    $scope['current'],
                ),
            ], $actor),
            $this->group('control', [
                $this->resourceCard(
                    'tenants',
                    'bi-people',
                    'tenant_directory',
                    'tenant_directory_description',
                    $this->url('tenants.index', $query),
                    $this->url('exports.resource', ['resource' => 'tenants', ...$query]),
                    'tenants',
                    $scope['current'],
                ),
                $this->resourceCard(
                    'documents',
                    'bi-file-earmark-lock',
                    'document_register',
                    'document_register_description',
                    $this->url('documents.index', $query),
                    $this->url('exports.resource', ['resource' => 'documents', ...$query]),
                    'documents',
                    $scope['period'],
                ),
                $actor->hasRole('superadmin')
                    ? $this->card(
                        'audit',
                        'bi-clock-history',
                        'audit_trail',
                        'audit_trail_description',
                        $this->url('audit-logs.index', $query),
                        [$this->download('XLSX', $this->url('audit-logs.export', $query))],
                        scope: $scope['audit'],
                    )
                    : null,
            ], $actor),
        ], static fn (array $group): bool => $group['cards'] !== []));
    }

    /**
     * @param  array<int, array<string, mixed>|null>  $cards
     * @return array{key:string,title:string,description:string,cards:array<int, array<string, mixed>>}
     */
    private function group(string $key, array $cards, User $actor): array
    {
        return [
            'key' => $key,
            'title' => trans("app.reports.library_group_{$key}"),
            'description' => trans("app.reports.library_group_{$key}_description"),
            'cards' => array_values(array_filter(
                $cards,
                fn (?array $card): bool => $card !== null
                    && ($card['module'] === null || PortfolioModules::enabledForUser($actor, $card['module'])),
            )),
        ];
    }

    /**
     * @param  array<int, array{label:string,href:string}>  $downloads
     * @param  array<int, array{label:string,value:string}>  $scope
     * @return array<string, mixed>
     */
    private function card(
        string $key,
        string $icon,
        string $titleKey,
        string $descriptionKey,
        string $openHref,
        array $downloads,
        ?string $module = null,
        array $scope = [],
    ): array {
        return [
            'key' => $key,
            'icon' => $icon,
            'title' => trans("app.reports.{$titleKey}"),
            'description' => trans("app.reports.{$descriptionKey}"),
            'openLabel' => trans('app.reports.open_source'),
            'openHref' => $openHref,
            'scope' => $scope,
            'downloads' => $downloads,
            'module' => $module,
        ];
    }

    /**
     * @param  array<int, array{label:string,value:string}>  $scope
     * @return array<string, mixed>
     */
    private function resourceCard(
        string $key,
        string $icon,
        string $titleKey,
        string $descriptionKey,
        string $openHref,
        string $downloadHref,
        string $module,
        array $scope,
    ): array {
        return $this->card(
            $key,
            $icon,
            $titleKey,
            $descriptionKey,
            $openHref,
            [$this->download('XLSX', $downloadHref)],
            $module,
            $scope,
        );
    }

    /** @return array{label:string,href:string} */
    private function download(string $format, string $href): array
    {
        return [
            'label' => trans('app.reports.download_format', ['format' => $format]),
            'href' => $href,
        ];
    }

    /** @param array<string, mixed> $parameters */
    private function url(string $route, array $parameters = []): string
    {
        return route($route, $parameters, false);
    }
}
