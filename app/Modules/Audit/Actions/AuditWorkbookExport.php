<?php

namespace App\Modules\Audit\Actions;

use App\Models\User;
use App\Modules\Audit\Presenters\AuditActivityPresenter;
use App\Modules\Audit\Queries\AuditLogQuery;
use App\Services\XlsxWorkbook;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AuditWorkbookExport
{
    public function __construct(
        private readonly AuditLogQuery $auditLogs,
        private readonly AuditActivityPresenter $presenter,
        private readonly XlsxWorkbook $workbook,
    ) {}

    /** @param array<string, mixed> $filters */
    public function download(User $actor, array $filters): BinaryFileResponse
    {
        $rows = [[
            trans('app.audit.export.date'),
            trans('app.audit.export.event'),
            trans('app.audit.export.subject_type'),
            trans('app.audit.export.subject'),
            trans('app.audit.export.causer'),
            trans('app.audit.export.description'),
            trans('app.audit.export.changed_fields'),
        ]];

        $this->auditLogs
            ->filtered($actor, $filters)
            ->reorder()
            ->chunkByIdDesc(500, function ($activities) use (&$rows): void {
                foreach ($activities as $activity) {
                    $payload = $this->presenter->row($activity);
                    $rows[] = [
                        $payload['created_at'],
                        $payload['event_label'],
                        $payload['subject_type_label'],
                        $payload['subject_label'],
                        $payload['causer_label'],
                        $payload['description'],
                        implode(', ', $payload['changed_keys']),
                    ];
                }
            });

        $path = $this->workbook->create($rows, trans('app.audit.export_sheet'));

        return response()->download(
            $path,
            'audit-log-'.now()->format('Ymd-His').'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        )->deleteFileAfterSend(true);
    }
}
