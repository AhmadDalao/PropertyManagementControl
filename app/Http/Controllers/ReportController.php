<?php

namespace App\Http\Controllers;

use App\Modules\Reports\Actions\ReportWorkbookExport;
use App\Modules\Reports\Presenters\ReportPagePresenter;
use App\Modules\Reports\Queries\PortfolioReportQuery;
use App\Modules\Reports\Queries\ReportPresetQuery;
use App\Modules\Reports\Requests\ReportIndexRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportPagePresenter $pagePresenter,
        private readonly PortfolioReportQuery $reports,
        private readonly ReportPresetQuery $presetQuery,
        private readonly ReportWorkbookExport $workbook,
    ) {}

    public function index(ReportIndexRequest $request): Response|RedirectResponse
    {
        $actor = $this->actor($request);

        if (! $request->hasExplicitFilters()) {
            $defaultFilters = $this->presetQuery->defaultFiltersFor($actor);

            if ($defaultFilters !== []) {
                $tab = $request->query('tab');

                if (in_array($tab, ['library', 'overview', 'collections', 'costs', 'operations'], true)) {
                    $defaultFilters['tab'] = $tab;
                }

                return to_route('reports.index', $defaultFilters);
            }
        }

        return Inertia::render(
            'admin/reports/index',
            $this->pagePresenter->present($actor, $request->filters()),
        );
    }

    public function export(ReportIndexRequest $request): BinaryFileResponse
    {
        $filters = $request->filters();
        $actor = $this->actor($request);
        $report = $this->reports->handle($actor, $filters, true);

        return $this->workbook->download($report, $filters, $actor);
    }
}
