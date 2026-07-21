<?php

namespace App\Http\Controllers;

use App\Models\ReportPreset;
use App\Modules\Reports\Actions\ManageReportPresets;
use App\Modules\Reports\Actions\ReportWorkbookExport;
use App\Modules\Reports\Presenters\ReportPagePresenter;
use App\Modules\Reports\Queries\PortfolioReportQuery;
use App\Modules\Reports\Queries\ReportPresetQuery;
use App\Modules\Reports\Requests\ReportIndexRequest;
use App\Modules\Reports\Requests\StoreReportPresetRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportPagePresenter $pagePresenter,
        private readonly PortfolioReportQuery $reports,
        private readonly ReportPresetQuery $presetQuery,
        private readonly ManageReportPresets $presets,
        private readonly ReportWorkbookExport $workbook,
    ) {}

    public function index(ReportIndexRequest $request): Response|RedirectResponse
    {
        $actor = $this->actor($request);

        if (! $request->hasExplicitFilters()) {
            $defaultFilters = $this->presetQuery->defaultFiltersFor($actor);

            if ($defaultFilters !== []) {
                $tab = $request->query('tab');

                if (in_array($tab, ['overview', 'collections', 'costs', 'operations'], true)) {
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

    public function storePreset(StoreReportPresetRequest $request): RedirectResponse
    {
        $preset = $this->presets->create($this->actor($request), $request->validated());

        return to_route('reports.index', $preset->filters_json ?? [])
            ->with('success', trans('app.messages.preset_saved'));
    }

    public function destroyPreset(Request $request, ReportPreset $reportPreset): RedirectResponse
    {
        $this->presets->delete($this->actor($request), $reportPreset);

        return to_route('reports.index')->with('success', trans('app.messages.preset_removed'));
    }

    public function export(ReportIndexRequest $request): BinaryFileResponse
    {
        $filters = $request->filters();
        $report = $this->reports->handle($this->actor($request), $filters);

        return $this->workbook->download($report, $filters);
    }
}
