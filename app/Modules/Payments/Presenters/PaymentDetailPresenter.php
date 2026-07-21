<?php

namespace App\Modules\Payments\Presenters;

use App\Models\Asset;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\User;
use App\Modules\Payments\Support\PaymentOptions;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Users\Support\UserAccess;

class PaymentDetailPresenter
{
    public function __construct(
        private readonly ResourcePresenter $resources,
        private readonly UserAccess $userAccess,
    ) {}

    /** @return array<string, mixed> */
    public function present(Payment $payment, User $actor): array
    {
        $payment->loadMissing([
            'portfolio',
            'lease.leaseable',
            'tenantProfile.user',
            'recordedBy',
            'allocations.leaseInstallment',
            'documents',
        ]);
        $adminMode = $actor->hasAnyRole(['superadmin', 'owner', 'property_manager']);
        $asset = $payment->lease?->leaseable instanceof Asset
            ? $payment->lease->leaseable
            : null;
        $assetTitle = $this->resources->localized($asset?->title_en, $asset?->title_ar);
        $portfolioName = $this->resources->localized(
            $payment->portfolio?->name_en,
            $payment->portfolio?->name_ar,
        );
        $actions = [];

        if ($adminMode) {
            $actions[] = ['label' => 'Review payment', 'href' => route('payments.edit', $payment), 'variant' => 'primary'];
        }

        if ($payment->status === 'posted') {
            $actions[] = ['label' => 'Download receipt', 'href' => route('payments.receipt', $payment), 'variant' => 'secondary'];
        }

        if ($payment->lease) {
            $actions[] = ['label' => 'Open lease', 'href' => route('leases.show', $payment->lease), 'variant' => 'secondary'];
        }

        $documents = $adminMode
            ? $payment->documents
            : $payment->documents
                ->where('is_public', true)
                ->whereIn('type', PaymentOptions::TENANT_DOCUMENT_TYPES);

        return [
            'header' => [
                'eyebrow' => 'Payment detail',
                'title' => $payment->reference ?: 'Payment #'.$payment->id,
                'description' => trim($payment->status.' · '.$payment->method.' · '.$payment->type),
                'backHref' => $adminMode ? route('payments.index') : route('dashboard'),
                'backLabel' => $adminMode ? 'All payments' : 'Dashboard',
                'actions' => $actions,
            ],
            'stats' => $this->resources->detailItems([
                ['label' => 'Amount', 'value' => number_format((float) $payment->amount, 2).' '.$payment->currency, 'tone' => 'primary'],
                ['label' => 'Allocated', 'value' => number_format((float) $payment->allocated_amount, 2).' '.$payment->currency, 'tone' => 'teal'],
                ['label' => 'Unallocated', 'value' => number_format((float) $payment->unallocated_amount, 2).' '.$payment->currency],
                ['label' => 'Status', 'value' => $payment->status, 'tone' => $payment->status === 'void' ? 'danger' : 'teal'],
            ]),
            'sections' => [[
                'title' => 'Payment record',
                'description' => 'Manual payment tracking and receipt context.',
                'items' => $this->resources->detailItems([
                    [
                        'label' => 'Tenant',
                        'value' => $payment->tenantProfile?->user?->name,
                        'href' => $adminMode && $payment->tenantProfile
                            ? route('tenants.show', $payment->tenantProfile)
                            : null,
                    ],
                    [
                        'label' => 'Lease',
                        'value' => $payment->lease?->code,
                        'href' => $payment->lease ? route('leases.show', $payment->lease) : null,
                    ],
                    [
                        'label' => 'Asset',
                        'value' => $assetTitle,
                        'href' => $adminMode && $asset ? route('assets.show', $asset) : null,
                    ],
                    [
                        'label' => 'Portfolio',
                        'value' => $portfolioName,
                        'href' => $adminMode && $payment->portfolio
                            ? route('portfolios.show', $payment->portfolio)
                            : null,
                    ],
                    [
                        'label' => 'Recorded by',
                        'value' => $adminMode ? $payment->recordedBy?->name : null,
                        'href' => $adminMode
                            ? $this->userAccess->recordHref($actor, $payment->recordedBy)
                            : null,
                    ],
                    ['label' => 'Received on', 'value' => $payment->received_on?->toDateString()],
                    ['label' => 'Notes', 'value' => $adminMode ? $payment->notes : null],
                ]),
            ]],
            'related' => [[
                'title' => 'Allocations',
                'description' => 'Installments this payment touched.',
                'columns' => ['Installment', 'Due date', 'Amount'],
                'rows' => $payment->allocations->map(fn (PaymentAllocation $allocation) => [
                    'Installment' => '#'.$allocation->leaseInstallment?->sequence,
                    'Due date' => $allocation->leaseInstallment?->due_date?->toDateString(),
                    'Amount' => number_format((float) $allocation->amount, 2).' '.$payment->currency,
                ])->all(),
                'emptyText' => 'No allocations yet. Pending and void payments do not allocate.',
            ]],
            'documents' => $this->resources->documentStrip($documents),
            'timeline' => $adminMode ? $this->resources->activityTimeline($payment) : [],
        ];
    }
}
