<?php

namespace App\Modules\Payments\Presenters;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Payments\Support\PaymentAccess;
use App\Modules\Payments\Support\PaymentOptions;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\ResourcePresenter;

class PaymentFormPresenter
{
    public function __construct(
        private readonly PaymentAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly ResourcePresenter $resources,
    ) {}

    /**
     * @param  array{lease_id?:mixed}  $defaults
     * @return array<string, mixed>
     */
    public function present(User $actor, ?Payment $payment = null, array $defaults = []): array
    {
        if ($payment) {
            $this->access->ensureCanManage($actor, $payment);

            return $this->editForm($payment);
        }

        $this->access->ensureManager($actor);

        return $this->createForm($actor, $defaults);
    }

    /** @return array<string, mixed> */
    private function editForm(Payment $payment): array
    {
        return [
            'title' => trans('app.actions.edit').' '.($payment->reference ?: '#'.$payment->id),
            'description' => 'Change payment state carefully. Voiding reverses allocations and keeps the audit trail.',
            'backHref' => route('payments.show', $payment),
            'backLabel' => 'Payment detail',
            'action' => route('payments.update', $payment),
            'method' => 'put',
            'submitLabel' => 'Update payment',
            'fields' => [
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $this->resources->fieldOptions(PaymentOptions::STATUSES)],
                ['name' => 'notes', 'label' => 'Internal notes', 'type' => 'textarea'],
            ],
            'initialValues' => [
                'status' => $payment->status,
                'notes' => $payment->notes ?? '',
            ],
        ];
    }

    /**
     * @param  array{lease_id?:mixed}  $defaults
     * @return array<string, mixed>
     */
    private function createForm(User $actor, array $defaults): array
    {
        $leases = $this->portfolios->apply(
            Lease::query()
                ->with(['portfolio', 'tenantProfile.user', 'leaseable', 'installments'])
                ->whereIn('status', PaymentOptions::PAYABLE_LEASE_STATUSES)
                ->orderByDesc('started_at'),
            $actor
        )->get();
        $leaseOptions = $leases->map(function (Lease $lease) use ($actor): array {
            $asset = $lease->leaseable instanceof Asset ? $lease->leaseable : null;
            $portfolio = $this->resources->localized(
                $lease->portfolio?->name_en,
                $lease->portfolio?->name_ar,
            );
            $label = implode(' · ', array_filter([
                $lease->code,
                $lease->tenantProfile?->user?->name,
                $this->resources->localized($asset?->title_en, $asset?->title_ar),
                trans('app.status.'.$lease->status),
                trans('app.payments.balance_remaining', [
                    'amount' => number_format((float) $lease->balance_remaining, 2),
                    'currency' => $lease->currency,
                ]),
                $actor->hasRole('superadmin') ? $portfolio : null,
            ]));

            return $this->option($lease->id, $label);
        })->all();
        $requestedLease = (string) ($defaults['lease_id'] ?? '');
        $selectedLease = collect($leaseOptions)->contains('value', $requestedLease)
            ? $requestedLease
            : (string) ($leaseOptions[0]['value'] ?? '');
        $fields = [
            ['name' => 'lease_id', 'label' => 'Lease', 'type' => 'select', 'required' => true, 'options' => $leaseOptions, 'help' => 'Tenant, portfolio, and currency are taken from this lease.'],
            ['name' => 'type', 'label' => 'Payment type', 'type' => 'select', 'options' => $this->resources->fieldOptions(PaymentOptions::TYPES)],
            ['name' => 'method', 'label' => 'Method', 'type' => 'select', 'options' => $this->resources->fieldOptions(PaymentOptions::METHODS)],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $this->resources->fieldOptions(PaymentOptions::CREATE_STATUSES)],
            ['name' => 'reference', 'label' => 'Bank or cash reference', 'help' => 'Leave blank to generate a unique system reference.'],
            ['name' => 'received_on', 'label' => 'Received on', 'type' => 'date', 'required' => true],
            ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'min' => 0.01, 'required' => true],
            ['name' => 'notes', 'label' => 'Internal notes', 'type' => 'textarea'],
        ];
        $fields = $this->resources->sectionFields($fields, [
            'Payment target' => [
                'description' => 'Choose the contract. Tenant, portfolio, and currency are derived automatically.',
                'fields' => ['lease_id', 'type'],
            ],
            'Payment evidence' => [
                'description' => 'Record when and how the money was received.',
                'fields' => ['method', 'status', 'reference', 'received_on', 'amount'],
            ],
            'Internal context' => [
                'description' => 'Notes stay inside the management workspace and never appear in the tenant portal.',
                'fields' => ['notes'],
            ],
        ]);

        return [
            'title' => 'Record payment',
            'description' => 'Post rent, deposit, or fees against a contract without duplicating tenant or portfolio data.',
            'backHref' => route('payments.index'),
            'backLabel' => 'All payments',
            'action' => route('payments.store'),
            'method' => 'post',
            'submitLabel' => 'Record payment',
            'fields' => $fields,
            'initialValues' => [
                'lease_id' => $selectedLease,
                'type' => 'rent',
                'method' => 'bank_transfer',
                'status' => 'posted',
                'reference' => '',
                'received_on' => now()->toDateString(),
                'amount' => 0,
                'notes' => '',
            ],
        ];
    }

    /** @return array{value:string,label:string} */
    private function option(int|string $value, string $label): array
    {
        return ['value' => (string) $value, 'label' => $label];
    }
}
