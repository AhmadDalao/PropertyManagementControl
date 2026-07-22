<?php

namespace App\Modules\Portfolios\Presenters;

use App\Models\User;
use App\Modules\Portfolios\Data\PortfolioDetailData;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Users\Support\UserAccess;
use App\Support\PortfolioModules;

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
        $enabledModules = collect(PortfolioModules::definitions())
            ->filter(fn (array $definition): bool => $data->settings[(string) $definition['key']] ?? true)
            ->pluck('label')
            ->join(', ');
        $net = $data->postedRevenue - $data->postedExpenses;

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
            'decisionCards' => [
                [
                    'title' => trans('app.portfolios.portfolio_status'),
                    'value' => trans("app.status.{$portfolio->status}"),
                    'detail' => trans('app.portfolios.module_count', ['count' => count(array_filter($data->settings))]),
                    'tone' => $portfolio->status === 'active' ? 'teal' : 'danger',
                    'icon' => 'bi-shield-check',
                ],
                [
                    'title' => trans('app.portfolios.recorded_valuation'),
                    'value' => $this->money($data->valuation, $currency),
                    'detail' => trans('app.portfolios.vacant_units', ['count' => $data->vacantAssets]),
                    'tone' => 'primary',
                    'icon' => 'bi-buildings',
                ],
                [
                    'title' => trans('app.portfolios.posted_revenue'),
                    'value' => $this->money($data->postedRevenue, $currency),
                    'detail' => trans('app.portfolios.active_lease_count', ['count' => $data->activeLeases]),
                    'tone' => 'teal',
                    'icon' => 'bi-cash-stack',
                ],
                [
                    'title' => trans('app.portfolios.net_position'),
                    'value' => $this->money($net, $currency),
                    'detail' => trans('app.portfolios.expense_summary', [
                        'amount' => $this->money($data->postedExpenses, $currency),
                    ]),
                    'tone' => $net >= 0 ? 'blue' : 'danger',
                    'icon' => 'bi-graph-up-arrow',
                ],
            ],
            'stats' => $this->resources->detailItems([
                ['label' => trans('app.portfolios.assets'), 'value' => $data->assetTotal, 'tone' => 'primary'],
                ['label' => trans('app.portfolios.users'), 'value' => $data->visibleUsers],
                ['label' => trans('app.portfolios.active_leases_label'), 'value' => $data->activeLeases, 'tone' => 'teal'],
                ['label' => trans('app.portfolios.open_maintenance'), 'value' => $data->openMaintenance, 'tone' => $data->openMaintenance > 0 ? 'danger' : 'muted'],
            ]),
            'sections' => [
                [
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
                    ]),
                ],
                [
                    'title' => trans('app.portfolios.ownership_modules'),
                    'description' => trans('app.portfolios.ownership_modules_help'),
                    'items' => $this->resources->detailItems([
                        [
                            'label' => trans('app.portfolios.owner'),
                            'value' => $portfolio->owner?->name ?: trans('app.portfolios.no_owner'),
                            'href' => $this->users->recordHref($actor, $portfolio->owner),
                        ],
                        ['label' => trans('app.portfolios.enabled_modules'), 'value' => $enabledModules],
                        ['label' => trans('app.portfolios.showcase_state'), 'value' => $portfolio->is_showcase ? trans('app.portfolios.showcase') : trans('app.portfolios.live_data')],
                        ['label' => trans('app.portfolios.created_at'), 'value' => $portfolio->created_at?->toDateTimeString()],
                        ['label' => trans('app.portfolios.updated_at'), 'value' => $portfolio->updated_at?->toDateTimeString()],
                    ]),
                ],
            ],
        ];
    }

    private function money(float $amount, string $currency): string
    {
        return number_format($amount, 2).' '.$currency;
    }
}
