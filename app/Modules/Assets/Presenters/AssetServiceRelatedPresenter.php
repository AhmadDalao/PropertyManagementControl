<?php

namespace App\Modules\Assets\Presenters;

use App\Models\ExpenseEntry;
use App\Models\MaintenanceRequest;
use App\Modules\Assets\Data\AssetDetailData;
use App\Modules\Expenses\Support\ExpenseOptions;
use App\Modules\Portfolios\Support\PortfolioModules;
use App\Modules\Shared\ResourcePresenter;

final readonly class AssetServiceRelatedPresenter
{
    public function __construct(private ResourcePresenter $resources) {}

    /** @return array<int, array<string, mixed>> */
    public function present(AssetDetailData $data): array
    {
        $sections = [];

        if (PortfolioModules::enabledForUser($data->actor, 'maintenance')) {
            $sections[] = $this->maintenance($data);
        }

        if (PortfolioModules::enabledForUser($data->actor, 'expenses')) {
            $sections[] = $this->expenses($data);
        }

        return $sections;
    }

    /** @return array<string, mixed> */
    private function maintenance(AssetDetailData $data): array
    {
        $request = trans('app.assets.request');
        $asset = trans('app.assets.unit_or_space');
        $status = trans('app.assets.status');
        $priority = trans('app.assets.priority');
        $open = trans('app.assets.open');

        return [
            'title' => trans('app.assets.maintenance'),
            'description' => trans('app.assets.property_maintenance_help'),
            'columns' => [$request, $asset, $status, $priority, $open],
            'rows' => $data->operations->maintenance->map(
                fn (MaintenanceRequest $item): array => [
                    $request => '#'.$item->id.' '.$item->title,
                    $asset => $this->resources->localized(
                        $item->asset?->title_en,
                        $item->asset?->title_ar,
                    ) ?: '-',
                    $status => trans("app.status.{$item->status}"),
                    $priority => trans("app.status.{$item->priority}"),
                    $open => [
                        'label' => $open,
                        'href' => route('maintenance-requests.show', $item),
                    ],
                ],
            )->all(),
            'emptyText' => trans('app.assets.no_property_maintenance'),
            'actionHref' => route('maintenance-requests.index', [
                'property_id' => $data->operations->propertyRoot->id,
            ]),
            'actionLabel' => trans('app.assets.review_maintenance'),
        ];
    }

    /** @return array<string, mixed> */
    private function expenses(AssetDetailData $data): array
    {
        $expense = trans('app.assets.expense');
        $asset = trans('app.assets.unit_or_space');
        $category = trans('app.assets.category');
        $amount = trans('app.assets.amount');
        $open = trans('app.assets.open');

        return [
            'title' => trans('app.assets.expenses'),
            'description' => trans('app.assets.property_expenses_help'),
            'columns' => [$expense, $asset, $category, $amount, $open],
            'rows' => $data->operations->expenses->map(fn (ExpenseEntry $item): array => [
                $expense => $item->title,
                $asset => $this->expenseAsset($item),
                $category => ExpenseOptions::label($item->category),
                $amount => $this->money((float) $item->amount, $item->currency),
                $open => ['label' => $open, 'href' => route('expenses.show', $item)],
            ])->all(),
            'emptyText' => trans('app.assets.no_property_expenses'),
            'actionHref' => route('expenses.index', [
                'property_id' => $data->operations->propertyRoot->id,
            ]),
            'actionLabel' => trans('app.assets.review_expenses'),
        ];
    }

    private function money(float $amount, string $currency): string
    {
        return number_format($amount, 2).' '.$currency;
    }

    private function expenseAsset(ExpenseEntry $expense): string
    {
        $asset = $expense->asset ?: $expense->lease?->leaseable;

        return $this->resources->localized(
            data_get($asset, 'title_en'),
            data_get($asset, 'title_ar'),
        ) ?: '-';
    }
}
