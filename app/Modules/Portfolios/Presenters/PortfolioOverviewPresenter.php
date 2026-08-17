<?php

namespace App\Modules\Portfolios\Presenters;

use App\Models\User;
use App\Modules\Portfolios\Data\PortfolioDetailData;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Users\Support\UserAccess;

class PortfolioOverviewPresenter
{
    public function __construct(
        private readonly ResourcePresenter $resources,
        private readonly UserAccess $users,
        private readonly PortfolioActionPresenter $actions,
    ) {}

    /** @return array<string, mixed> */
    public function present(PortfolioDetailData $data, User $actor): array
    {
        $portfolio = $data->portfolio;
        $currency = $portfolio->default_currency;
        $name = $this->resources->localized($portfolio->name_en, $portfolio->name_ar)
            ?? $portfolio->code;
        $location = implode(' · ', array_filter([$portfolio->city, $portfolio->country]));
        $net = $data->postedRevenue - $data->postedExpenses;
        $platformAccess = $actor->hasRole('superadmin');
        $visible = fn (string $module): bool => $platformAccess || ($data->settings[$module] ?? true);
        $financialVisible = collect(['assets', 'payments', 'expenses', 'reports'])
            ->contains($visible);
        $stats = [
            ['label' => trans('app.portfolios.portfolio_status'), 'value' => trans("app.status.{$portfolio->status}"), 'tone' => $portfolio->status === 'active' ? 'teal' : 'danger'],
        ];

        if ($visible('assets')) {
            $stats[] = ['label' => trans('app.portfolios.properties'), 'value' => $data->assetTotal, 'tone' => 'primary'];
            $stats[] = ['label' => trans('app.portfolios.vacant_rentable'), 'value' => $data->vacantAssets, 'tone' => $data->vacantAssets > 0 ? 'danger' : 'muted'];
        }

        if ($visible('users')) {
            $stats[] = ['label' => trans('app.portfolios.people_count'), 'value' => $data->visibleUsers];
        }

        if ($visible('leases')) {
            $stats[] = ['label' => trans('app.portfolios.active_leases_label'), 'value' => $data->activeLeases, 'tone' => 'teal'];
        }

        if ($visible('maintenance')) {
            $stats[] = ['label' => trans('app.portfolios.open_service'), 'value' => $data->openMaintenance, 'tone' => $data->openMaintenance > 0 ? 'danger' : 'muted'];
        }

        return [
            'header' => [
                'eyebrow' => trans('app.portfolios.detail_eyebrow'),
                'title' => $name,
                'description' => trans('app.portfolios.detail_description', [
                    'code' => $portfolio->code,
                    'location' => $location ?: trans('app.portfolios.location_not_set'),
                ]),
                'backHref' => route('portfolios.index'),
                'backLabel' => trans('app.portfolios.all_portfolios'),
                'actions' => $this->actions->present($portfolio, $actor, $data->settings),
            ],
            'stats' => $this->resources->detailItems($stats),
            'sections' => array_values(array_filter([
                [
                    'key' => 'profile',
                    'title' => trans('app.portfolios.business_profile'),
                    'description' => trans('app.portfolios.business_profile_help'),
                    'items' => $this->resources->detailItems([
                        ['label' => trans('app.portfolios.name_ar'), 'value' => $portfolio->name_ar],
                        ['label' => trans('app.portfolios.code'), 'value' => $portfolio->code],
                        ['label' => trans('app.portfolios.status'), 'value' => trans("app.status.{$portfolio->status}")],
                        ['label' => trans('app.portfolios.default_currency'), 'value' => $currency],
                        ['label' => trans('app.portfolios.contact_email'), 'value' => $portfolio->contact_email],
                        ['label' => trans('app.portfolios.contact_phone'), 'value' => $portfolio->contact_phone],
                        ['label' => trans('app.portfolios.location'), 'value' => $location],
                        ['label' => trans('app.portfolios.address'), 'value' => $this->resources->localized($portfolio->address, $portfolio->address_ar)],
                        ['label' => trans('app.portfolios.showcase_state'), 'value' => $portfolio->is_showcase ? trans('app.portfolios.showcase') : trans('app.portfolios.live_data')],
                    ]),
                ],
                $visible('users') ? [
                    'key' => 'ownership',
                    'title' => trans('app.portfolios.ownership_modules'),
                    'description' => trans('app.portfolios.ownership_modules_help'),
                    'items' => $this->resources->detailItems([
                        [
                            'label' => trans('app.portfolios.owner'),
                            'value' => $portfolio->owner?->name ?: trans('app.portfolios.no_owner'),
                            'href' => $this->users->recordHref($actor, $portfolio->owner),
                        ],
                        ['label' => trans('app.portfolios.enabled_modules'), 'value' => trans('app.portfolios.module_count', ['count' => count(array_filter($data->settings))])],
                        ['label' => trans('app.portfolios.created_at'), 'value' => $portfolio->created_at?->toDateTimeString()],
                        ['label' => trans('app.portfolios.updated_at'), 'value' => $portfolio->updated_at?->toDateTimeString()],
                    ]),
                ] : null,
                $financialVisible ? [
                    'key' => 'financial',
                    'title' => trans('app.portfolios.financial_position'),
                    'description' => trans('app.portfolios.financial_position_help'),
                    'items' => $this->resources->detailItems(array_values(array_filter([
                        $visible('assets') ? [
                            'label' => trans('app.portfolios.recorded_valuation'),
                            'value' => $this->money($data->valuation, $currency),
                            'href' => route('assets.index', ['portfolio_id' => $portfolio->id]),
                        ] : null,
                        $visible('payments') ? [
                            'label' => trans('app.portfolios.posted_revenue'),
                            'value' => $this->money($data->postedRevenue, $currency),
                            'href' => route('payments.index', ['portfolio_id' => $portfolio->id, 'status' => 'posted']),
                        ] : null,
                        $visible('expenses') ? [
                            'label' => trans('app.portfolios.posted_expenses'),
                            'value' => $this->money($data->postedExpenses, $currency),
                            'href' => route('expenses.index', ['portfolio_id' => $portfolio->id, 'status' => 'posted']),
                        ] : null,
                        $visible('payments') && $visible('expenses') ? [
                            'label' => trans('app.portfolios.net_position'),
                            'value' => $this->money($net, $currency),
                            'href' => $visible('reports') ? route('reports.statement', ['portfolio_id' => $portfolio->id]) : null,
                            'tone' => $net < 0 ? 'danger' : 'teal',
                        ] : null,
                    ]))),
                ] : null,
            ])),
        ];
    }

    private function money(float $amount, string $currency): string
    {
        return number_format($amount, 2).' '.$currency;
    }
}
