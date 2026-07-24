<?php

namespace App\Modules\RentCollection\Actions;

use App\Models\Asset;
use App\Models\LeaseInstallment;
use App\Models\User;
use App\Modules\Exports\Contracts\ResourceExporter;
use App\Modules\Exports\Support\ResourceWorkbook;
use App\Modules\RentCollection\Presenters\RentCollectionRowPresenter;
use App\Modules\RentCollection\Queries\RentCollectionIndexQuery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class RentCollectionWorkbookExport implements ResourceExporter
{
    public function __construct(
        private RentCollectionIndexQuery $collections,
        private RentCollectionRowPresenter $rows,
        private ResourceWorkbook $workbook,
    ) {}

    public function download(Request $request, User $actor): BinaryFileResponse
    {
        [$rootByAsset, $assetsById] = $this->collections->assetContext(
            $actor,
            filter_var($request->query('portfolio_id'), FILTER_VALIDATE_INT) ?: null,
        );

        return $this->workbook->download(
            'rent-collection',
            [
                trans('app.rent_collection.installment'),
                trans('app.rent_collection.type'),
                trans('app.rent_collection.tenant'),
                trans('app.rent_collection.lease'),
                trans('app.rent_collection.property'),
                trans('app.rent_collection.asset'),
                trans('app.rent_collection.due_date'),
                trans('app.rent_collection.amount_due'),
                trans('app.rent_collection.amount_paid'),
                trans('app.rent_collection.outstanding'),
                trans('app.rent_collection.status'),
                trans('app.rent_collection.currency'),
            ],
            $this->collections->forExport($request, $actor),
            function (LeaseInstallment $installment) use ($rootByAsset, $assetsById): array {
                $assetId = $installment->lease?->leaseable instanceof Asset
                    ? $installment->lease->leaseable->id
                    : null;
                $rootId = $assetId !== null ? ($rootByAsset[$assetId] ?? null) : null;
                $row = $this->rows->present(
                    $installment,
                    $rootId !== null ? ($assetsById[$rootId] ?? null) : null,
                );

                return [
                    $row['label'],
                    trans("app.rent_collection.type_{$row['line_type']}"),
                    data_get($row, 'tenant.name'),
                    data_get($row, 'lease.code'),
                    $this->localizedName($row['property'] ?? null),
                    $this->localizedName($row['asset'] ?? null),
                    $this->workbook->date($installment->due_date),
                    $row['amount_due'],
                    $row['amount_paid'],
                    $row['outstanding_amount'],
                    trans("app.status.{$row['status']}"),
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
}
