<?php

namespace App\Modules\Assets\Presenters;

use App\Models\Asset;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Modules\Assets\Data\AssetDetailData;
use App\Modules\Assets\Support\AssetLeaseBalance;
use App\Modules\Expenses\Support\ExpenseOptions;
use App\Modules\Shared\ResourcePresenter;

class AssetRelatedPresenter
{
    public function __construct(
        private readonly AssetLeaseBalance $balances,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function present(AssetDetailData $data): array
    {
        return [
            $this->children($data),
            $this->leases($data),
            $this->maintenance($data),
            $this->expenses($data),
        ];
    }

    /** @return array<string, mixed> */
    private function children(AssetDetailData $data): array
    {
        $assetLabel = trans('app.assets.asset');
        $type = trans('app.assets.type');
        $occupancy = trans('app.assets.occupancy');
        $open = trans('app.assets.open');

        return [
            'title' => trans('app.assets.child_assets'),
            'description' => trans('app.assets.child_assets_help'),
            'columns' => [$assetLabel, $type, $occupancy, $open],
            'rows' => $data->children->map(fn (Asset $asset): array => [
                $assetLabel => $this->resources->localized($asset->title_en, $asset->title_ar),
                $type => trans("app.assets.types.{$asset->asset_type}"),
                $occupancy => trans("app.status.{$asset->occupancy_status}"),
                $open => ['label' => $open, 'href' => route('assets.show', $asset)],
            ])->all(),
            'emptyText' => trans('app.assets.no_children'),
            'actionHref' => route('assets.create', ['parent_id' => $data->asset->id]),
            'actionLabel' => trans('app.assets.add_child'),
        ];
    }

    /** @return array<string, mixed> */
    private function leases(AssetDetailData $data): array
    {
        $leaseLabel = trans('app.assets.lease');
        $tenant = trans('app.assets.tenant');
        $status = trans('app.assets.status');
        $balance = trans('app.assets.balance');
        $open = trans('app.assets.open');

        return [
            'title' => trans('app.assets.leases'),
            'description' => trans('app.assets.leases_help'),
            'columns' => [$leaseLabel, $tenant, $status, $balance, $open],
            'rows' => $data->leases->map(fn (Lease $lease): array => [
                $leaseLabel => $lease->code,
                $tenant => data_get($lease, 'tenantProfile.user.name', '-'),
                $status => trans("app.status.{$lease->status}"),
                $balance => $this->money($this->balances->remaining($lease), $lease->currency),
                $open => ['label' => $open, 'href' => route('leases.show', $lease)],
            ])->all(),
            'emptyText' => trans('app.assets.no_leases'),
            'actionHref' => route('leases.create', ['asset_id' => $data->asset->id]),
            'actionLabel' => trans('app.assets.create_lease'),
        ];
    }

    /** @return array<string, mixed> */
    private function maintenance(AssetDetailData $data): array
    {
        $request = trans('app.assets.request');
        $tenant = trans('app.assets.tenant');
        $status = trans('app.assets.status');
        $priority = trans('app.assets.priority');
        $open = trans('app.assets.open');

        return [
            'title' => trans('app.assets.maintenance'),
            'description' => trans('app.assets.maintenance_help'),
            'columns' => [$request, $tenant, $status, $priority, $open],
            'rows' => $data->maintenance->map(fn (MaintenanceRequest $item): array => [
                $request => '#'.$item->id.' '.$item->title,
                $tenant => data_get($item, 'tenantProfile.user.name', '-'),
                $status => trans("app.status.{$item->status}"),
                $priority => trans("app.status.{$item->priority}"),
                $open => ['label' => $open, 'href' => route('maintenance-requests.show', $item)],
            ])->all(),
            'emptyText' => trans('app.assets.no_maintenance'),
            'actionHref' => route('maintenance-requests.create', ['asset_id' => $data->asset->id]),
            'actionLabel' => trans('app.assets.create_request'),
        ];
    }

    /** @return array<string, mixed> */
    private function expenses(AssetDetailData $data): array
    {
        $expense = trans('app.assets.expense');
        $category = trans('app.assets.category');
        $status = trans('app.assets.status');
        $amount = trans('app.assets.amount');
        $open = trans('app.assets.open');

        return [
            'title' => trans('app.assets.expenses'),
            'description' => trans('app.assets.expenses_help'),
            'columns' => [$expense, $category, $status, $amount, $open],
            'rows' => $data->expenses->map(fn (ExpenseEntry $item): array => [
                $expense => $item->title,
                $category => ExpenseOptions::label($item->category),
                $status => trans("app.status.{$item->status}"),
                $amount => $this->money((float) $item->amount, $item->currency),
                $open => ['label' => $open, 'href' => route('expenses.show', $item)],
            ])->all(),
            'emptyText' => trans('app.assets.no_expenses'),
            'actionHref' => route('expenses.create', ['asset_id' => $data->asset->id]),
            'actionLabel' => trans('app.assets.add_expense'),
        ];
    }

    private function money(float $amount, string $currency): string
    {
        return number_format($amount, 2).' '.$currency;
    }
}
