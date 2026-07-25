<?php

namespace App\Modules\LeaseMoveOuts\Actions;

use App\Models\Asset;
use App\Models\LeaseMoveOut;
use App\Models\User;
use App\Modules\Exports\Contracts\ResourceExporter;
use App\Modules\Exports\Support\ResourceWorkbook;
use App\Modules\LeaseMoveOuts\Presenters\LeaseMoveOutRowPresenter;
use App\Modules\LeaseMoveOuts\Queries\LeaseMoveOutIndexQuery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class LeaseMoveOutWorkbookExport implements ResourceExporter
{
    public function __construct(
        private LeaseMoveOutIndexQuery $moveOuts,
        private LeaseMoveOutRowPresenter $rows,
        private ResourceWorkbook $workbook,
    ) {}

    public function download(Request $request, User $actor): BinaryFileResponse
    {
        [$rootByAsset, $assetsById] = $this->moveOuts->assetContext(
            $actor,
            filter_var($request->query('portfolio_id'), FILTER_VALIDATE_INT) ?: null,
        );

        return $this->workbook->download(
            'lease-move-outs',
            [
                trans('app.lease_move_outs.lease'),
                trans('app.lease_move_outs.tenant'),
                trans('app.lease_move_outs.property'),
                trans('app.lease_move_outs.asset'),
                trans('app.lease_move_outs.move_out_date'),
                trans('app.lease_move_outs.reason'),
                trans('app.lease_move_outs.state'),
                trans('app.lease_move_outs.deposit_disposition'),
                trans('app.lease_move_outs.deposit_deduction'),
                trans('app.lease_move_outs.keys_returned'),
                trans('app.lease_move_outs.notice'),
                trans('app.lease_move_outs.inspection'),
                trans('app.lease_move_outs.outstanding'),
                trans('app.lease_move_outs.currency'),
            ],
            $this->moveOuts->forExport($request, $actor),
            function (LeaseMoveOut $moveOut) use ($rootByAsset, $assetsById): array {
                $assetId = $moveOut->lease?->leaseable instanceof Asset
                    ? $moveOut->lease->leaseable->id
                    : null;
                $rootId = $assetId !== null ? ($rootByAsset[$assetId] ?? null) : null;
                $row = $this->rows->present(
                    $moveOut,
                    $rootId !== null ? ($assetsById[$rootId] ?? null) : null,
                );

                return [
                    $row['code'],
                    data_get($row, 'tenant.name'),
                    $this->localizedName($row['property'] ?? null),
                    $this->localizedName($row['asset'] ?? null),
                    $this->workbook->date($moveOut->move_out_date),
                    trans("app.lease_move_outs.reason_{$row['reason']}"),
                    trans("app.lease_move_outs.state_{$row['state']}"),
                    trans("app.lease_move_outs.deposit_{$row['deposit_disposition']}"),
                    $row['deposit_deduction_amount'],
                    $this->yesNo($row['keys_returned']),
                    $this->yesNo($row['notice_uploaded']),
                    $this->yesNo($row['inspection_uploaded']),
                    $row['outstanding_amount'],
                    $row['currency'],
                ];
            },
        );
    }

    /** @param array<string, mixed>|null $record */
    private function localizedName(?array $record): ?string
    {
        if ($record === null) {
            return null;
        }

        $primary = app()->isLocale('ar') ? $record['title_ar'] : $record['title_en'];
        $fallback = app()->isLocale('ar') ? $record['title_en'] : $record['title_ar'];

        return $primary ?: $fallback;
    }

    private function yesNo(bool $value): string
    {
        return trans($value ? 'app.common.yes' : 'app.common.no');
    }
}
