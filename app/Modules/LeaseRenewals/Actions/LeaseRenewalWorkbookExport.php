<?php

namespace App\Modules\LeaseRenewals\Actions;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\User;
use App\Modules\Exports\Contracts\ResourceExporter;
use App\Modules\Exports\Support\ResourceWorkbook;
use App\Modules\LeaseRenewals\Presenters\LeaseRenewalRowPresenter;
use App\Modules\LeaseRenewals\Queries\LeaseRenewalIndexQuery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class LeaseRenewalWorkbookExport implements ResourceExporter
{
    public function __construct(
        private LeaseRenewalIndexQuery $renewals,
        private LeaseRenewalRowPresenter $rows,
        private ResourceWorkbook $workbook,
    ) {}

    public function download(Request $request, User $actor): BinaryFileResponse
    {
        [$rootByAsset, $assetsById] = $this->renewals->assetContext(
            $actor,
            filter_var($request->query('portfolio_id'), FILTER_VALIDATE_INT) ?: null,
        );

        return $this->workbook->download(
            'lease-renewals',
            [
                trans('app.lease_renewals.lease'),
                trans('app.lease_renewals.tenant'),
                trans('app.lease_renewals.property'),
                trans('app.lease_renewals.asset'),
                trans('app.lease_renewals.end_date'),
                trans('app.lease_renewals.days_remaining'),
                trans('app.lease_renewals.contact_due'),
                trans('app.lease_renewals.renewal_state'),
                trans('app.lease_renewals.renewal_contract'),
                trans('app.lease_renewals.rent'),
                trans('app.lease_renewals.outstanding'),
                trans('app.lease_renewals.currency'),
            ],
            $this->renewals->forExport($request, $actor),
            function (Lease $lease) use ($rootByAsset, $assetsById): array {
                $assetId = $lease->leaseable instanceof Asset
                    ? $lease->leaseable->id
                    : null;
                $rootId = $assetId !== null ? ($rootByAsset[$assetId] ?? null) : null;
                $row = $this->rows->present(
                    $lease,
                    $rootId !== null ? ($assetsById[$rootId] ?? null) : null,
                );

                return [
                    $row['code'],
                    data_get($row, 'tenant.name'),
                    $this->localizedName($row['property'] ?? null),
                    $this->localizedName($row['asset'] ?? null),
                    $this->workbook->date($lease->ends_at),
                    $row['days_remaining'],
                    $this->workbook->date(
                        $lease->ends_at?->copy()->subDays((int) $lease->renewal_notice_days),
                    ),
                    trans("app.lease_renewals.state_{$row['renewal_state']}"),
                    data_get($row, 'renewal.code'),
                    $row['rent_amount'],
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
}
