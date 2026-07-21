<?php

namespace App\Modules\Tenants\Presenters;

use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Payments\Support\PaymentOptions;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Tenants\Support\TenantAccess;
use Illuminate\Support\Collection;

class TenantDetailPresenter
{
    public function __construct(
        private readonly TenantAccess $access,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(TenantProfile $tenant, User $actor): array
    {
        $this->access->ensureCanManage($actor, $tenant);
        $tenant->loadMissing(['portfolio', 'user']);

        $activeLease = $tenant->leases()
            ->with(['leaseable', 'installments', 'documents'])
            ->where('status', 'active')
            ->latest('started_at')
            ->first();
        $payableLease = $activeLease ?? $tenant->leases()
            ->with('installments')
            ->whereIn('status', PaymentOptions::PAYABLE_LEASE_STATUSES)
            ->latest('started_at')
            ->first();
        $recentLeases = $tenant->leases()
            ->with(['leaseable', 'installments'])
            ->latest('started_at')
            ->limit(8)
            ->get();
        $recentPayments = $tenant->payments()
            ->with('lease')
            ->latest('received_on')
            ->limit(8)
            ->get();
        $recentMaintenance = $tenant->maintenanceRequests()
            ->with('asset')
            ->latest('requested_at')
            ->limit(8)
            ->get();
        $paidAmount = 0.0;

        if ($activeLease !== null) {
            $paidAmount = (float) $activeLease->total_paid;
        }
        $openMaintenance = $tenant->maintenanceRequests()->whereIn('status', ['open', 'in_progress'])->count();
        $tenantUser = $tenant->user;
        $activeLeaseCurrency = $activeLease ? $activeLease->currency : 'SAR';
        $lastPayment = $activeLease?->payments()
            ->where('status', 'posted')
            ->latest('received_on')
            ->first();
        $headerActions = [
            ['label' => trans('app.tenants.edit_tenant'), 'href' => route('tenants.edit', $tenant), 'variant' => 'primary'],
            ['label' => trans('app.tenants.create_lease'), 'href' => route('leases.create', ['tenant_profile_id' => $tenant->id]), 'variant' => 'secondary'],
        ];

        if ($payableLease) {
            $headerActions[] = [
                'label' => trans('app.tenants.record_payment'),
                'href' => route('payments.create', ['lease_id' => $payableLease->id]),
                'variant' => 'secondary',
            ];
        }

        $tenantName = $tenantUser && filled($tenantUser->name)
            ? $tenantUser->name
            : ($tenant->company_name ?: trans('app.tenants.tenant_number', ['id' => $tenant->id]));

        return [
            'header' => [
                'eyebrow' => trans('app.tenants.detail_eyebrow'),
                'title' => $tenantName,
                'description' => trans('app.tenants.detail_description', [
                    'profile' => trans("app.tenants.{$tenant->profile_type}"),
                    'status' => trans("app.status.{$tenant->status}"),
                ]),
                'backHref' => route('tenants.index'),
                'backLabel' => trans('app.tenants.all_tenants'),
                'actions' => $headerActions,
            ],
            'decisionCards' => [
                [
                    'title' => trans('app.tenants.portal_account'),
                    'value' => trans('app.status.'.($tenantUser ? $tenantUser->status : 'inactive')),
                    'detail' => $tenantUser ? $tenantUser->email : trans('app.tenants.no_login_account'),
                    'tone' => $tenantUser?->status === 'active' ? 'teal' : 'danger',
                    'icon' => 'bi-person-lock',
                ],
                [
                    'title' => trans('app.tenants.current_rental'),
                    'value' => $activeLease ? $activeLease->code : trans('app.tenants.no_active_lease'),
                    'detail' => $this->resources->localized(
                        $activeLease?->leaseable?->getAttribute('title_en'),
                        $activeLease?->leaseable?->getAttribute('title_ar'),
                    ),
                    'href' => $activeLease ? route('leases.show', $activeLease) : route('leases.create', ['tenant_profile_id' => $tenant->id]),
                    'actionLabel' => $activeLease ? trans('app.tenants.open_lease') : trans('app.tenants.create_lease'),
                    'tone' => $activeLease ? 'primary' : 'muted',
                    'icon' => 'bi-file-earmark-text',
                ],
                [
                    'title' => trans('app.tenants.contract_balance'),
                    'value' => $activeLease
                        ? number_format((float) $activeLease->balance_remaining, 2).' '.$activeLease->currency
                        : '0.00 SAR',
                    'detail' => trans('app.tenants.total_paid_value', [
                        'amount' => number_format($paidAmount, 2),
                        'currency' => $activeLeaseCurrency,
                    ]),
                    'tone' => $activeLease && $activeLease->balance_remaining > 0 ? 'danger' : 'teal',
                    'icon' => 'bi-wallet2',
                ],
                [
                    'title' => trans('app.tenants.open_maintenance'),
                    'value' => $openMaintenance,
                    'detail' => trans('app.tenants.recent_requests_count', ['count' => $recentMaintenance->count()]),
                    'tone' => $openMaintenance > 0 ? 'danger' : 'muted',
                    'icon' => 'bi-tools',
                ],
            ],
            'stats' => $this->resources->detailItems([
                ['label' => trans('app.tenants.status'), 'value' => trans("app.status.{$tenant->status}"), 'tone' => $tenant->status === 'active' ? 'teal' : 'muted'],
                ['label' => trans('app.tenants.active_leases_label'), 'value' => $tenant->leases()->where('status', 'active')->count(), 'tone' => 'primary'],
                ['label' => trans('app.tenants.paid'), 'value' => number_format($paidAmount, 2).' '.$activeLeaseCurrency],
                ['label' => trans('app.tenants.open_maintenance'), 'value' => $openMaintenance, 'tone' => $openMaintenance > 0 ? 'danger' : 'muted'],
            ]),
            'sections' => [
                [
                    'title' => trans('app.tenants.profile_section'),
                    'description' => trans('app.tenants.profile_section_help'),
                    'items' => $this->resources->detailItems([
                        ['label' => trans('app.tenants.email'), 'value' => $tenantUser?->email],
                        ['label' => trans('app.tenants.phone'), 'value' => $tenantUser?->phone],
                        ['label' => trans('app.tenants.portal_language'), 'value' => trans('app.tenants.'.($tenantUser?->preferred_locale === 'ar' ? 'arabic' : 'english'))],
                        ['label' => trans('app.tenants.portfolio'), 'value' => $this->resources->localized($tenant->portfolio?->name_en, $tenant->portfolio?->name_ar), 'href' => $tenant->portfolio ? route('portfolios.show', $tenant->portfolio) : null],
                        ['label' => trans('app.tenants.profile_type'), 'value' => trans("app.tenants.{$tenant->profile_type}")],
                        ['label' => trans('app.tenants.national_id'), 'value' => $tenant->national_id],
                        ['label' => trans('app.tenants.company_name'), 'value' => $tenant->company_name],
                        ['label' => trans('app.tenants.emergency_contact'), 'value' => trim(($tenant->emergency_contact_name ?? '').' '.($tenant->emergency_contact_phone ?? ''))],
                        ['label' => trans('app.tenants.address'), 'value' => $tenant->address],
                        ['label' => trans('app.tenants.notes'), 'value' => $tenant->notes],
                    ]),
                ],
                [
                    'title' => trans('app.tenants.financial_position'),
                    'description' => trans('app.tenants.financial_position_help'),
                    'tab' => 'financial',
                    'items' => $this->resources->detailItems([
                        ['label' => trans('app.tenants.active_contract'), 'value' => $activeLease?->code, 'href' => $activeLease ? route('leases.show', $activeLease) : null],
                        ['label' => trans('app.tenants.contract_ends'), 'value' => $activeLease?->ends_at?->toDateString()],
                        ['label' => trans('app.tenants.total_paid'), 'value' => number_format($paidAmount, 2).' '.$activeLeaseCurrency],
                        ['label' => trans('app.tenants.contract_balance'), 'value' => $activeLease ? number_format((float) $activeLease->balance_remaining, 2).' '.$activeLease->currency : null],
                        ['label' => trans('app.tenants.last_payment'), 'value' => $lastPayment?->received_on?->toDateString()],
                    ]),
                ],
            ],
            'related' => [
                $this->leaseTable($recentLeases, $tenant),
                $this->paymentTable($recentPayments),
                $this->maintenanceTable($recentMaintenance),
            ],
            'documents' => $activeLease ? $this->resources->documentStrip($activeLease->documents) : [],
            'timeline' => $this->resources->activityTimeline($tenant),
        ];
    }

    /**
     * @param  Collection<int, Lease>  $leases
     * @return array<string, mixed>
     */
    private function leaseTable(Collection $leases, TenantProfile $tenant): array
    {
        return [
            'title' => trans('app.tenants.leases'),
            'description' => trans('app.tenants.leases_help'),
            'columns' => [
                trans('app.tenants.lease'),
                trans('app.tenants.asset'),
                trans('app.tenants.status'),
                trans('app.tenants.balance'),
            ],
            'rows' => $leases->map(fn (Lease $lease): array => [
                trans('app.tenants.lease') => ['label' => $lease->code, 'href' => route('leases.show', $lease)],
                trans('app.tenants.asset') => $lease->leaseable
                    ? [
                        'label' => $this->resources->localized(
                            $lease->leaseable->getAttribute('title_en'),
                            $lease->leaseable->getAttribute('title_ar'),
                        ) ?? '-',
                        'href' => route('assets.show', $lease->leaseable),
                    ]
                    : '-',
                trans('app.tenants.status') => trans("app.status.{$lease->status}"),
                trans('app.tenants.balance') => number_format((float) $lease->balance_remaining, 2).' '.$lease->currency,
            ])->all(),
            'emptyText' => trans('app.tenants.no_leases'),
            'actionHref' => route('leases.create', ['tenant_profile_id' => $tenant->id]),
            'actionLabel' => trans('app.tenants.create_lease'),
        ];
    }

    /**
     * @param  Collection<int, Payment>  $payments
     * @return array<string, mixed>
     */
    private function paymentTable(Collection $payments): array
    {
        return [
            'title' => trans('app.tenants.payments'),
            'description' => trans('app.tenants.payments_help'),
            'columns' => [
                trans('app.tenants.reference'),
                trans('app.tenants.received_on'),
                trans('app.tenants.amount'),
                trans('app.tenants.status'),
            ],
            'rows' => $payments->map(fn (Payment $payment): array => [
                trans('app.tenants.reference') => [
                    'label' => $payment->reference ?: trans('app.payments.payment_number', ['id' => $payment->id]),
                    'href' => route('payments.show', $payment),
                ],
                trans('app.tenants.received_on') => $payment->received_on?->toDateString() ?? '-',
                trans('app.tenants.amount') => number_format((float) $payment->amount, 2).' '.$payment->currency,
                trans('app.tenants.status') => trans("app.status.{$payment->status}"),
            ])->all(),
            'emptyText' => trans('app.tenants.no_payments'),
        ];
    }

    /**
     * @param  Collection<int, MaintenanceRequest>  $requests
     * @return array<string, mixed>
     */
    private function maintenanceTable(Collection $requests): array
    {
        return [
            'title' => trans('app.tenants.maintenance'),
            'description' => trans('app.tenants.maintenance_help'),
            'columns' => [
                trans('app.tenants.request'),
                trans('app.tenants.asset'),
                trans('app.tenants.status'),
                trans('app.tenants.priority'),
            ],
            'rows' => $requests->map(fn (MaintenanceRequest $request): array => [
                trans('app.tenants.request') => [
                    'label' => '#'.$request->id.' '.$request->title,
                    'href' => route('maintenance-requests.show', $request),
                ],
                trans('app.tenants.asset') => $request->asset
                    ? [
                        'label' => $this->resources->localized($request->asset->title_en, $request->asset->title_ar) ?? '-',
                        'href' => route('assets.show', $request->asset),
                    ]
                    : '-',
                trans('app.tenants.status') => trans("app.status.{$request->status}"),
                trans('app.tenants.priority') => trans("app.status.{$request->priority}"),
            ])->all(),
            'emptyText' => trans('app.tenants.no_maintenance'),
        ];
    }
}
