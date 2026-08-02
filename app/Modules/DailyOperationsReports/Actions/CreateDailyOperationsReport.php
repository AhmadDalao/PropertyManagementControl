<?php

namespace App\Modules\DailyOperationsReports\Actions;

use App\Models\DailyOperationsReportRun;
use App\Modules\ActionCenter\Actions\ActionCenterReportFiles;
use App\Modules\ActionCenter\Queries\ActionCenterReportQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final readonly class CreateDailyOperationsReport
{
    public function __construct(
        private ActionCenterReportQuery $reports,
        private ActionCenterReportFiles $files,
        private PruneDailyOperationsReports $prune,
    ) {}

    public function handle(int $runId): DailyOperationsReportRun
    {
        $run = DB::transaction(function () use ($runId): DailyOperationsReportRun {
            $locked = DailyOperationsReportRun::query()
                ->with('initiatedBy')
                ->lockForUpdate()
                ->findOrFail($runId);

            if ($locked->status !== 'queued') {
                throw new RuntimeException(trans('app.daily_reports.run_not_queued'));
            }

            if ($locked->initiatedBy === null) {
                throw new RuntimeException(trans('app.daily_reports.actor_missing'));
            }
            $locked->update([
                'status' => 'running',
                'started_at' => now(),
                'failed_at' => null,
                'failure_summary' => null,
            ]);

            return $locked->refresh();
        }, 3);

        $actor = $run->initiatedBy;

        if ($actor === null) {
            throw new RuntimeException(trans('app.daily_reports.actor_missing'));
        }
        $data = $this->reports->handle($actor, [
            'search' => '',
            'type' => 'all',
            'priority' => 'all',
            'assignee' => 'all',
            'portfolio_id' => $run->portfolio_id,
            'property_id' => null,
            'per_page' => 12,
            'page' => 1,
        ]);
        $disk = Storage::disk($run->storage_disk);
        $scope = $run->portfolio_id ? 'portfolio-'.$run->portfolio_id : 'global';
        $directory = 'daily-operations-reports/'.$run->report_date->format('Y-m-d').'/'.$scope;
        $staging = $directory.'/.partial-'.$run->id.'-'.Str::lower(Str::random(8));
        $basename = sprintf('daily-operations-%s-%06d', $scope, $run->id);
        $paths = [
            'pdf' => $directory.'/'.$basename.'.pdf',
            'docx' => $directory.'/'.$basename.'.docx',
            'xlsx' => $directory.'/'.$basename.'.xlsx',
        ];

        try {
            $disk->makeDirectory($staging);
            $content = [
                'pdf' => $this->files->pdf($data),
                'docx' => $this->files->docx($data),
                'xlsx' => $this->files->xlsx($data),
            ];

            foreach ($content as $format => $binary) {
                $partial = $staging.'/'.$basename.'.'.$format;
                throw_unless($disk->put($partial, $binary), RuntimeException::class, 'Report file write failed.');
                throw_unless($disk->move($partial, $paths[$format]), RuntimeException::class, 'Report file move failed.');
            }

            $run->update([
                'status' => 'completed',
                'pdf_path' => $paths['pdf'],
                'docx_path' => $paths['docx'],
                'xlsx_path' => $paths['xlsx'],
                'pdf_bytes' => $disk->size($paths['pdf']),
                'docx_bytes' => $disk->size($paths['docx']),
                'xlsx_bytes' => $disk->size($paths['xlsx']),
                'item_count' => (int) data_get($data, 'summary.total', 0),
                'summary_json' => [
                    'priority' => $data['summary'],
                    'types' => $data['typePositions'],
                    'currencies' => $data['currencyPositions'],
                ],
                'scope_json' => $data['scope'],
                'completed_at' => now(),
            ]);
            $this->prune->handle();

            return $run->refresh();
        } catch (Throwable $exception) {
            foreach ($paths as $path) {
                $disk->delete($path);
            }

            $run->update([
                'status' => 'failed',
                'failure_summary' => $this->safeFailure($exception),
                'failed_at' => now(),
            ]);

            throw $exception;
        } finally {
            $disk->deleteDirectory($staging);
            $localStaging = $disk->path($staging);

            if (File::isDirectory($localStaging)) {
                File::deleteDirectory($localStaging);
            }
        }
    }

    private function safeFailure(Throwable $exception): string
    {
        return Str::limit(str_replace(
            [base_path(), storage_path()],
            ['[application]', '[storage]'],
            $exception->getMessage(),
        ), 1000);
    }
}
