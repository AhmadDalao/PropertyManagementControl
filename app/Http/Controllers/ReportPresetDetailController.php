<?php

namespace App\Http\Controllers;

use App\Models\ReportPreset;
use App\Modules\Reports\Presenters\ReportPresetDetailPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ReportPresetDetailController extends Controller
{
    public function __construct(
        private readonly ReportPresetDetailPresenter $details,
    ) {}

    public function __invoke(Request $request, ReportPreset $reportPreset): Response
    {
        return Inertia::render('admin/resource-show', [
            'detailPage' => $this->details->present(
                $this->actor($request),
                $reportPreset,
            ),
        ]);
    }
}
