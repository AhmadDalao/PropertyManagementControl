<?php

namespace App\Modules\LeaseRenewals\Presenters;

final class LeaseRenewalDownloadPresenter
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{pdf:string,docx:string,xlsx:string}
     */
    public function present(array $filters): array
    {
        $query = array_filter([
            'search' => $filters['search'],
            'queue' => $filters['queue'] !== 'attention' ? $filters['queue'] : null,
            'horizon' => $filters['horizon'] !== '90' ? $filters['horizon'] : null,
            'lease_status' => $filters['lease_status'] !== 'all'
                ? $filters['lease_status']
                : null,
            'portfolio_id' => $filters['portfolio_id'],
            'property_id' => $filters['property_id'] !== 'all'
                ? $filters['property_id']
                : null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        return [
            'pdf' => route('lease-renewals.report.pdf', $query, false),
            'docx' => route('lease-renewals.report.word', $query, false),
            'xlsx' => route('exports.resource', [
                'resource' => 'lease-renewals',
                ...$query,
            ], false),
        ];
    }
}
