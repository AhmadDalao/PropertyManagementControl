<?php

namespace App\Modules\Portfolios\Presenters;

use App\Models\User;
use App\Modules\Portfolios\Data\PortfolioDetailData;
use App\Modules\Shared\Authorization\AssignedPropertyScope;

final class PortfolioWorkflowPresenter
{
    public function __construct(private readonly AssignedPropertyScope $assignments) {}

    /**
     * @param  array<string, mixed>|null  $progress
     * @return array<string, mixed>
     */
    public function present(PortfolioDetailData $data, User $actor, ?array $progress): array
    {
        $portfolio = $data->portfolio;
        $visible = fn (string $module): bool => $actor->hasRole('superadmin')
            || ($data->settings[$module] ?? true);
        $current = null;

        foreach (($progress['steps'] ?? []) as $step) {
            if (is_array($step) && ($step['state'] ?? null) === 'current') {
                $current = $step;
                break;
            }
        }

        if (is_array($current) && isset($current['href'], $current['actionLabel'])) {
            return $this->workflow(
                'continue_setup',
                $progress['summary'] ?? null,
                'primary',
                'bi-list-check',
                [$this->action($current['actionLabel'], $current['href'], 'primary')],
            );
        }

        if ($actor->hasRole('property_manager') && ! $this->assignments->hasAssignments($actor)) {
            return $this->workflow(
                'assignment_required',
                null,
                'danger',
                'bi-person-gear',
            );
        }

        if ($visible('maintenance') && $data->openMaintenance > 0) {
            return $this->workflow(
                'resolve_open_service',
                trans('app.portfolios.open_request_count', ['count' => $data->openMaintenance]),
                'danger',
                'bi-tools',
                [$this->action(
                    trans('app.portfolios.review_service'),
                    route('maintenance-requests.index', [
                        'portfolio_id' => $portfolio->id,
                        'status' => 'open',
                    ]),
                    'primary',
                )],
            );
        }

        if ($visible('assets') && $data->vacantAssets > 0) {
            return $this->workflow(
                'activate_vacant_space',
                trans('app.portfolios.vacant_units', ['count' => $data->vacantAssets]),
                'primary',
                'bi-building-check',
                [$this->action(
                    trans('app.portfolios.review_vacancies'),
                    route('assets.index', [
                        'portfolio_id' => $portfolio->id,
                        'rentable' => 'yes',
                        'occupancy_status' => 'vacant',
                    ]),
                    'primary',
                )],
            );
        }

        $net = $data->postedRevenue - $data->postedExpenses;

        if ($net < 0 && $visible('payments') && $visible('expenses') && $visible('reports')) {
            return $this->workflow(
                'review_negative_position',
                $this->money($net, $portfolio->default_currency),
                'danger',
                'bi-graph-down-arrow',
                [$this->action(
                    trans('app.portfolios.open_operating_statement'),
                    route('reports.statement', ['portfolio_id' => $portfolio->id]),
                    'primary',
                )],
            );
        }

        return $this->workflow(
            'portfolio_on_track',
            trans('app.portfolios.no_immediate_portfolio_risk'),
            'teal',
            'bi-check2-circle',
            [$this->action(
                trans('app.portfolios.open_action_center'),
                route('action-center.index', ['portfolio_id' => $portfolio->id]),
                'secondary',
            )],
        );
    }

    /**
     * @param  list<array<string, string>>  $actions
     * @return array<string, mixed>
     */
    private function workflow(
        string $key,
        ?string $status,
        string $tone,
        string $icon,
        array $actions = [],
    ): array {
        return [
            'eyebrow' => trans('app.portfolios.next_portfolio_action'),
            'title' => trans("app.portfolios.{$key}"),
            'description' => trans("app.portfolios.{$key}_help"),
            'status' => $status,
            'tone' => $tone,
            'icon' => $icon,
            'actions' => $actions,
        ];
    }

    /** @return array{label:string,href:string,variant:string} */
    private function action(string $label, string $href, string $variant): array
    {
        return compact('label', 'href', 'variant');
    }

    private function money(float $amount, string $currency): string
    {
        return number_format($amount, 2).' '.$currency;
    }
}
