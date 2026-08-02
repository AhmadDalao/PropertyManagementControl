<?php

namespace App\Http\Controllers;

use App\Models\SystemBackupRun;
use App\Modules\SystemBackups\Actions\DeleteSystemBackup;
use App\Modules\SystemBackups\Actions\StartSystemBackup;
use App\Modules\SystemBackups\Jobs\CreateSystemBackupJob;
use App\Modules\SystemBackups\Queries\SystemBackupIndexQuery;
use App\Modules\SystemBackups\Requests\SystemBackupIndexRequest;
use App\Modules\SystemBackups\Support\SystemBackupAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class SystemBackupController extends Controller
{
    public function __construct(
        private readonly SystemBackupIndexQuery $backups,
        private readonly StartSystemBackup $start,
        private readonly DeleteSystemBackup $delete,
        private readonly SystemBackupAccess $access,
    ) {}

    public function index(SystemBackupIndexRequest $request): Response
    {
        return Inertia::render(
            'admin/system-backups/index',
            $this->backups->handle($request, $this->actor($request)),
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $run = $this->start->create($this->actor($request));
        CreateSystemBackupJob::dispatch($run->id);

        return to_route('system-backups.index')
            ->with('success', trans('app.backups.queued'));
    }

    public function download(Request $request, SystemBackupRun $systemBackupRun): StreamedResponse
    {
        $this->access->ensureSuperadmin($this->actor($request));

        abort_unless(
            $systemBackupRun->status === 'completed'
            && $systemBackupRun->archive_path
            && Storage::disk($systemBackupRun->archive_disk)->exists($systemBackupRun->archive_path),
            404,
        );

        return Storage::disk($systemBackupRun->archive_disk)->download(
            $systemBackupRun->archive_path,
            basename($systemBackupRun->archive_path),
            ['Content-Type' => 'application/gzip'],
        );
    }

    public function destroy(Request $request, SystemBackupRun $systemBackupRun): RedirectResponse
    {
        $this->delete->handle($this->actor($request), $systemBackupRun);

        return to_route('system-backups.index')
            ->with('success', trans('app.backups.pruned'));
    }
}
