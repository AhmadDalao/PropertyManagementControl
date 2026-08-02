<?php

namespace App\Modules\DailyOperationsReports\Actions;

use App\Models\DailyOperationsReportRun;
use Illuminate\Support\Facades\Storage;

final class PruneDailyOperationsReports
{
    public function handle(): int
    {
        $cutoff = today()->subDays(max(
            1,
            (int) config('operations.daily_report_retention_days', 90),
        ));
        $pruned = 0;

        DailyOperationsReportRun::query()
            ->where('status', 'completed')
            ->whereDate('report_date', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($runs) use (&$pruned): void {
                foreach ($runs as $run) {
                    $this->prune($run);
                    $pruned++;
                }
            });

        return $pruned;
    }

    public function prune(DailyOperationsReportRun $run): DailyOperationsReportRun
    {
        $disk = Storage::disk($run->storage_disk);
        $disk->delete(array_filter([
            $run->pdf_path,
            $run->docx_path,
            $run->xlsx_path,
        ]));
        $run->update([
            'status' => 'pruned',
            'pdf_path' => null,
            'docx_path' => null,
            'xlsx_path' => null,
        ]);

        return $run->refresh();
    }
}
