<?php

namespace App\Http\Controllers;

use App\Models\DailyOperationsReportRun;
use App\Modules\DailyOperationsReports\Actions\PruneDailyOperationsReports;
use App\Modules\DailyOperationsReports\Actions\StartDailyOperationsReport;
use App\Modules\DailyOperationsReports\Jobs\CreateDailyOperationsReportJob;
use App\Modules\DailyOperationsReports\Queries\DailyOperationsReportQuery;
use App\Modules\DailyOperationsReports\Requests\DailyOperationsReportIndexRequest;
use App\Modules\DailyOperationsReports\Requests\StartDailyOperationsReportRequest;
use App\Modules\DailyOperationsReports\Support\DailyOperationsReportAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DailyOperationsReportController extends Controller
{
    public function __construct(
        private readonly DailyOperationsReportQuery $reports,
        private readonly StartDailyOperationsReport $start,
        private readonly PruneDailyOperationsReports $prune,
        private readonly DailyOperationsReportAccess $access,
    ) {}

    public function index(DailyOperationsReportIndexRequest $request): Response
    {
        return Inertia::render(
            'admin/reports/daily-operations/index',
            $this->reports->index($request, $this->actor($request)),
        );
    }

    public function store(StartDailyOperationsReportRequest $request): RedirectResponse
    {
        $run = $this->start->create(
            $this->actor($request),
            $request->portfolioId(),
        );
        CreateDailyOperationsReportJob::dispatch($run->id);

        return to_route('reports.daily-operations.index')
            ->with('success', trans('app.daily_reports.queued'));
    }

    public function show(Request $request, DailyOperationsReportRun $dailyOperationsReportRun): Response
    {
        return Inertia::render(
            'admin/reports/daily-operations/show',
            $this->reports->show($this->actor($request), $dailyOperationsReportRun),
        );
    }

    public function download(
        Request $request,
        DailyOperationsReportRun $dailyOperationsReportRun,
        string $format,
    ): StreamedResponse {
        $this->access->ensureCanAccess($this->actor($request), $dailyOperationsReportRun);
        abort_unless(in_array($format, ['pdf', 'docx', 'xlsx'], true), 404);
        $path = $dailyOperationsReportRun->{$format.'_path'};
        abort_unless(
            $dailyOperationsReportRun->status === 'completed'
            && $path
            && Storage::disk($dailyOperationsReportRun->storage_disk)->exists($path),
            404,
        );
        $mime = match ($format) {
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        };

        return Storage::disk($dailyOperationsReportRun->storage_disk)->download(
            $path,
            basename($path),
            ['Content-Type' => $mime],
        );
    }

    public function destroy(
        Request $request,
        DailyOperationsReportRun $dailyOperationsReportRun,
    ): RedirectResponse {
        $this->access->ensureCanAccess($this->actor($request), $dailyOperationsReportRun);
        abort_unless($dailyOperationsReportRun->status === 'completed', 422);
        $this->prune->prune($dailyOperationsReportRun);

        return to_route('reports.daily-operations.index')
            ->with('success', trans('app.daily_reports.pruned'));
    }
}
