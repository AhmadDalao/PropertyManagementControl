<?php

namespace App\Modules\Portfolios\Actions;

use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Exports\Contracts\ResourceExporter;
use App\Modules\Exports\Support\ResourceWorkbook;
use App\Modules\Portfolios\Queries\PortfolioIndexQuery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PortfolioWorkbookExport implements ResourceExporter
{
    public function __construct(
        private readonly PortfolioIndexQuery $portfolios,
        private readonly ResourceWorkbook $workbook,
    ) {}

    public function download(Request $request, User $actor): BinaryFileResponse
    {
        return $this->workbook->download('portfolios', [
            trans('app.portfolios.code'),
            trans('app.portfolios.name_en'),
            trans('app.portfolios.name_ar'),
            trans('app.portfolios.owner'),
            trans('app.portfolios.status'),
            trans('app.portfolios.city'),
            trans('app.portfolios.country'),
            trans('app.portfolios.users'),
            trans('app.portfolios.assets'),
            trans('app.portfolios.leases'),
            trans('app.portfolios.recorded_valuation'),
            trans('app.portfolios.posted_revenue'),
            trans('app.portfolios.posted_expenses'),
            trans('app.portfolios.default_currency'),
        ], $this->portfolios->forExport($request, $actor), fn (Portfolio $portfolio): array => [
            $portfolio->code,
            $portfolio->name_en,
            $portfolio->name_ar,
            $portfolio->owner?->name,
            $this->workbook->option($portfolio->status),
            $portfolio->city,
            $portfolio->country,
            $portfolio->users_count,
            $portfolio->assets_count,
            $portfolio->leases_count,
            $portfolio->valuation_total,
            $portfolio->posted_revenue_total,
            $portfolio->posted_expense_total,
            $portfolio->default_currency,
        ]);
    }
}
