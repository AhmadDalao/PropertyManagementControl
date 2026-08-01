<?php

namespace App\Http\Controllers;

use App\Models\ReportPreset;
use App\Modules\Reports\Actions\ManageReportPresets;
use App\Modules\Reports\Presenters\ReportPresetPagePresenter;
use App\Modules\Reports\Requests\ReportIndexRequest;
use App\Modules\Reports\Requests\StoreReportPresetRequest;
use App\Modules\Reports\Requests\UpdateReportPresetRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ReportPresetController extends Controller
{
    public function __construct(
        private readonly ReportPresetPagePresenter $pages,
        private readonly ManageReportPresets $presets,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('admin/reports/saved', $this->pages->index($this->actor($request)));
    }

    public function create(ReportIndexRequest $request): Response
    {
        return Inertia::render(
            'admin/reports/saved-form',
            $this->pages->create($this->actor($request), $request->filters()),
        );
    }

    public function edit(Request $request, ReportPreset $reportPreset): Response
    {
        return Inertia::render(
            'admin/reports/saved-form',
            $this->pages->edit($this->actor($request), $reportPreset),
        );
    }

    public function store(StoreReportPresetRequest $request): RedirectResponse
    {
        $this->presets->create($this->actor($request), $request->validated());

        return to_route('reports.saved.index')->with('success', trans('app.messages.preset_saved'));
    }

    public function update(
        UpdateReportPresetRequest $request,
        ReportPreset $reportPreset,
    ): RedirectResponse {
        $this->presets->update($this->actor($request), $reportPreset, $request->validated());

        return to_route('reports.saved.index')->with('success', trans('app.messages.preset_updated'));
    }

    public function duplicate(Request $request, ReportPreset $reportPreset): RedirectResponse
    {
        $this->presets->duplicate($this->actor($request), $reportPreset);

        return to_route('reports.saved.index')->with('success', trans('app.messages.preset_duplicated'));
    }

    public function destroy(Request $request, ReportPreset $reportPreset): RedirectResponse
    {
        $this->presets->delete($this->actor($request), $reportPreset);

        return to_route('reports.saved.index')->with('success', trans('app.messages.preset_removed'));
    }
}
