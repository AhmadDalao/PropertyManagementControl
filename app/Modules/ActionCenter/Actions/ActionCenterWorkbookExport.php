<?php

namespace App\Modules\ActionCenter\Actions;

use App\Models\User;
use App\Modules\ActionCenter\Queries\ActionCenterReportQuery;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class ActionCenterWorkbookExport
{
    public function __construct(
        private ActionCenterReportQuery $report,
        private ActionCenterReportFiles $files,
    ) {}

    /**
     * @param array{
     *     search:string,type:string,priority:string,assignee:string,
     *     portfolio_id:int|null,property_id:int|null,per_page:int,page:int
     * } $filters
     */
    public function download(User $actor, array $filters): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'pmc-action-center-');
        throw_if($path === false, \RuntimeException::class, 'Unable to create report workbook.');
        file_put_contents(
            $path,
            $this->files->xlsx($this->report->handle($actor, $filters)),
        );

        return response()->download(
            $path,
            'action-center-'.now()->format('Ymd-His').'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        )->deleteFileAfterSend(true);
    }
}
