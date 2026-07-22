<?php

namespace App\Modules\Assets\Actions;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Assets\Queries\AssetIndexQuery;
use App\Modules\Exports\Contracts\ResourceExporter;
use App\Modules\Exports\Support\ResourceWorkbook;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssetWorkbookExport implements ResourceExporter
{
    public function __construct(
        private readonly AssetIndexQuery $assets,
        private readonly ResourceWorkbook $workbook,
    ) {}

    public function download(Request $request, User $actor): BinaryFileResponse
    {
        return $this->workbook->download('assets', [
            trans('app.assets.code'),
            trans('app.assets.title_en'),
            trans('app.assets.title_ar'),
            trans('app.assets.type'),
            trans('app.assets.usage'),
            trans('app.assets.occupancy'),
            trans('app.assets.status'),
            trans('app.assets.rentable'),
            trans('app.assets.value'),
            trans('app.assets.currency'),
            trans('app.assets.parent_asset'),
            trans('app.assets.portfolio'),
        ], $this->assets->forExport($request, $actor), fn (Asset $asset): array => [
            $asset->code,
            $asset->title_en,
            $asset->title_ar,
            trans("app.assets.types.{$asset->asset_type}"),
            trans("app.assets.usages.{$asset->usage_type}"),
            $this->workbook->option($asset->occupancy_status),
            $this->workbook->option($asset->status),
            $this->workbook->yesNo($asset->rentable),
            $asset->valuation_amount,
            $asset->currency,
            $this->workbook->localized($asset->parent, 'title_en', 'title_ar'),
            $this->workbook->localized($asset->portfolio, 'name_en', 'name_ar'),
        ]);
    }
}
