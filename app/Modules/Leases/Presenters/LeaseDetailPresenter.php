<?php

namespace App\Modules\Leases\Presenters;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Leases\Support\LeaseOptions;
use App\Modules\Shared\ResourcePresenter;

class LeaseDetailPresenter
{
    public function __construct(private readonly ResourcePresenter $resources) {}

    /**
     * @return array<string, mixed>
     */
    public function present(Lease $lease, User $actor): array
    {
        $lease->loadMissing([
            'portfolio',
            'tenantProfile.user',
            'leaseable',
            'managedBy',
            'installments',
            'payments.allocations.leaseInstallment',
            'documents',
        ]);
        $adminMode = $actor->hasAnyRole(['superadmin', 'owner', 'property_manager']);
        $asset = $lease->leaseable instanceof Asset ? $lease->leaseable : null;
        $assetTitle = $this->resources->localized($asset?->title_en, $asset?->title_ar);
        $portfolioName = $this->resources->localized($lease->portfolio?->name_en, $lease->portfolio?->name_ar);
        $actions = [];

        if ($adminMode) {
            $actions[] = ['label' => 'Edit lease', 'href' => route('leases.edit', $lease), 'variant' => 'primary'];
        }

        $actions[] = ['label' => 'Contract PDF', 'href' => route('leases.contract', $lease), 'variant' => 'secondary'];

        if ($adminMode) {
            $actions[] = [
                'label' => 'Upload signed PDF',
                'href' => route('documents.create', [
                    'documentable_type' => 'lease',
                    'documentable_id' => $lease->id,
                    'type' => 'signed_contract',
                    'title_en' => "Signed contract {$lease->code}",
                    'title_ar' => "العقد الموقع {$lease->code}",
                ]),
                'variant' => 'secondary',
            ];
        }

        $actions[] = ['label' => 'Tenant statement', 'href' => route('leases.statement', $lease), 'variant' => 'secondary'];

        if ($adminMode) {
            $actions[] = [
                'label' => 'Record payment',
                'href' => route('payments.create', ['lease_id' => $lease->id]),
                'variant' => 'secondary',
            ];
        }

        $documents = $adminMode
            ? $lease->documents
            : $lease->documents
                ->where('is_public', true)
                ->whereIn('type', LeaseOptions::TENANT_DOCUMENT_TYPES);

        return [
            'header' => [
                'eyebrow' => 'Lease detail',
                'title' => $lease->code,
                'description' => trim(($lease->tenantProfile?->user->name ?? 'Tenant').' · '.($assetTitle ?? 'Asset').' · '.$lease->status),
                'backHref' => $adminMode ? route('leases.index') : route('dashboard'),
                'backLabel' => $adminMode ? 'All leases' : 'Dashboard',
                'actions' => $actions,
            ],
            'stats' => $this->resources->detailItems([
                ['label' => 'Total due', 'value' => number_format((float) $lease->total_due, 2).' '.$lease->currency, 'tone' => 'primary'],
                ['label' => 'Paid', 'value' => number_format((float) $lease->total_paid, 2).' '.$lease->currency, 'tone' => 'teal'],
                ['label' => 'Remaining', 'value' => number_format((float) $lease->balance_remaining, 2).' '.$lease->currency, 'tone' => $lease->balance_remaining > 0 ? 'danger' : 'teal'],
                ['label' => 'Days left', 'value' => $lease->days_remaining ?? 'Ended'],
            ]),
            'sections' => [
                [
                    'title' => 'Contract',
                    'description' => 'Tenant, rented asset, dates, billing, and manager.',
                    'items' => $this->resources->detailItems([
                        [
                            'label' => 'Tenant',
                            'value' => $lease->tenantProfile?->user?->name,
                            'href' => $adminMode && $lease->tenantProfile
                                ? route('tenants.show', $lease->tenantProfile)
                                : null,
                        ],
                        [
                            'label' => 'Asset',
                            'value' => $assetTitle,
                            'href' => $adminMode && $asset ? route('assets.show', $asset) : null,
                        ],
                        [
                            'label' => 'Portfolio',
                            'value' => $portfolioName,
                            'href' => $adminMode && $lease->portfolio
                                ? route('portfolios.show', $lease->portfolio)
                                : null,
                        ],
                        [
                            'label' => 'Managed by',
                            'value' => $lease->managedBy?->name,
                            'href' => $adminMode && $lease->managedBy
                                ? route('users.show', $lease->managedBy)
                                : null,
                        ],
                        ['label' => 'Started', 'value' => $lease->started_at?->toDateString()],
                        ['label' => 'Ends', 'value' => $lease->ends_at?->toDateString()],
                        ['label' => 'Signed', 'value' => $lease->signed_at?->toDateString() ?? 'Not signed'],
                        ['label' => 'Frequency', 'value' => $lease->payment_frequency],
                        ['label' => 'Notes', 'value' => $adminMode ? $lease->notes : null],
                    ]),
                ],
                [
                    'title' => 'Amounts',
                    'description' => 'Operational rent values used to generate installments.',
                    'items' => $this->resources->detailItems([
                        ['label' => 'Rent amount', 'value' => number_format((float) $lease->rent_amount, 2).' '.$lease->currency],
                        ['label' => 'Deposit', 'value' => number_format((float) $lease->deposit_amount, 2).' '.$lease->currency],
                        ['label' => 'Tax', 'value' => number_format((float) $lease->tax_amount, 2).' '.$lease->currency],
                        ['label' => 'Discount', 'value' => number_format((float) $lease->discount_amount, 2).' '.$lease->currency],
                        ['label' => 'Billing day', 'value' => $lease->billing_day],
                    ]),
                ],
            ],
            'related' => [
                [
                    'title' => 'Installments',
                    'description' => 'Due schedule and allocation state.',
                    'columns' => ['#', 'Installment', 'Due date', 'Status', 'Due', 'Paid'],
                    'rows' => $lease->installments->map(fn (LeaseInstallment $installment) => [
                        '#' => $installment->sequence,
                        'Installment' => $installment->label,
                        'Due date' => $installment->due_date?->toDateString(),
                        'Status' => $installment->status,
                        'Due' => number_format((float) $installment->amount_due, 2),
                        'Paid' => number_format((float) $installment->amount_paid, 2),
                    ])->all(),
                    'emptyText' => 'No installments generated yet.',
                ],
                [
                    'title' => 'Payments',
                    'description' => 'Money posted against this lease.',
                    'columns' => ['Payment', 'Date', 'Status', 'Amount'],
                    'rows' => $lease->payments->map(fn (Payment $payment) => [
                        'Payment' => $payment->reference ?: '#'.$payment->id,
                        'Date' => $payment->received_on?->toDateString(),
                        'Status' => $payment->status,
                        'Amount' => number_format((float) $payment->amount, 2).' '.$payment->currency,
                    ])->all(),
                    'emptyText' => 'No payments recorded yet.',
                    'actionHref' => $adminMode ? route('payments.create', ['lease_id' => $lease->id]) : null,
                    'actionLabel' => $adminMode ? 'Record payment' : null,
                ],
            ],
            'documents' => $this->resources->documentStrip($documents),
            'timeline' => $adminMode ? $this->resources->activityTimeline($lease) : [],
        ];
    }
}
