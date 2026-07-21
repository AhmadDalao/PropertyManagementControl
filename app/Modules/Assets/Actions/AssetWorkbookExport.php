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
            'Code',
            'Title',
            'Arabic Title',
            'Type',
            'Usage',
            'Occupancy',
            'Status',
            'Rentable',
            'Value',
            'Currency',
            'Parent',
            'Portfolio',
        ], $this->assets->forExport($request, $actor), fn (Asset $asset): array => [
            $asset->code,
            $asset->title_en,
            $asset->title_ar,
            $this->workbook->option($asset->asset_type),
            $this->workbook->option($asset->usage_type),
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
