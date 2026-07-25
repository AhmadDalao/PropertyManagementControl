<?php

namespace App\Modules\ActionCenter\Actions;

use App\Models\User;
use App\Modules\ActionCenter\Queries\ActionCenterIndexQuery;
use App\Modules\Exports\Support\XlsxWorkbook;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class ActionCenterWorkbookExport
{
    public function __construct(
        private ActionCenterIndexQuery $actions,
        private XlsxWorkbook $workbook,
    ) {}

    /**
     * @param array{
     *     search:string,type:string,priority:string,assignee:string,
     *     portfolio_id:int|null,property_id:int|null,per_page:int,page:int
     * } $filters
     */
    public function download(User $actor, array $filters): BinaryFileResponse
    {
        $rows = [[
            trans('app.action_center.export_type'),
            trans('app.action_center.export_priority'),
            trans('app.action_center.export_record'),
            trans('app.action_center.export_tenant'),
            trans('app.action_center.export_asset'),
            trans('app.action_center.export_portfolio'),
            trans('app.action_center.export_status'),
            trans('app.action_center.export_due_on'),
            trans('app.action_center.export_assignee'),
            trans('app.action_center.export_amount'),
            trans('app.action_center.export_currency'),
            trans('app.action_center.export_link'),
        ]];

        foreach ($this->actions->exportItems($actor, $filters) as $item) {
            $rows[] = [
                trans('app.action_center.type_'.$item['type']),
                trans('app.action_center.priority_'.$item['priority']),
                $item['title'],
                $item['tenant'],
                $this->localized($item['asset'] ?? null, 'title'),
                $this->localized($item['portfolio'] ?? null, 'name'),
                trans('app.action_center.status_'.$item['status']),
                $item['due_on'],
                data_get($item, 'assigned_to.name'),
                $item['amount'],
                $item['currency'],
                url((string) $item['href']),
            ];
        }

        $path = $this->workbook->create(
            $rows,
            trans('app.action_center.export_sheet'),
        );

        return response()->download(
            $path,
            'action-center-'.now()->format('Ymd-His').'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        )->deleteFileAfterSend(true);
    }

    /** @param array<string, mixed>|null $record */
    private function localized(?array $record, string $prefix): ?string
    {
        if ($record === null) {
            return null;
        }

        $primary = app()->isLocale('ar')
            ? $record[$prefix.'_ar']
            : $record[$prefix.'_en'];
        $fallback = app()->isLocale('ar')
            ? $record[$prefix.'_en']
            : $record[$prefix.'_ar'];

        return $primary ?: $fallback;
    }
}
