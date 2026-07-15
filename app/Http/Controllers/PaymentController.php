<?php

namespace App\Http\Controllers;

use App\Models\Lease;
use App\Models\Payment;
use App\Models\TenantProfile;
use App\Models\User;
use App\Services\LeaseFinancialService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function __construct(private readonly LeaseFinancialService $leaseFinancials) {}

    public function index(Request $request): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        $filters = $this->tableFilters($request, [
            'status' => 'all',
            'type' => 'all',
            'method' => 'all',
            'date_from' => '',
            'date_to' => '',
        ]);
        $baseQuery = $this->scopeByPortfolio(Payment::query(), $actor);
        $payments = (clone $baseQuery)->with([
            'lease.installments',
            'lease.leaseable',
            'tenantProfile.user',
            'allocations.leaseInstallment',
        ]);

        $this->applyExactFilter($payments, $filters, 'portfolio_id');
        $this->applyExactFilter($payments, $filters, 'status');
        $this->applyExactFilter($payments, $filters, 'type');
        $this->applyExactFilter($payments, $filters, 'method');
        $this->applyDateRange($payments, $filters, 'received_on');
        $this->applySearch($payments, $filters['search'], [
            'reference',
            'notes',
            fn ($query, $search, $like) => $query->orWhereHas(
                'lease',
                fn ($leaseQuery) => $leaseQuery->where('code', 'like', $like)
            ),
            fn ($query, $search, $like) => $query->orWhereHas(
                'tenantProfile.user',
                fn ($userQuery) => $userQuery->where('name', 'like', $like)->orWhere('email', 'like', $like)
            ),
        ]);

        return Inertia::render('admin/payments/index', [
            'payments' => $this->paginateTable($payments, $request, $filters, [
                'created_at',
                'received_on',
                'reference',
                'status',
                'type',
                'method',
                'amount',
            ], 'received_on')->through(fn (Payment $payment) => $this->paymentTableRow($payment)),
            'paymentInsights' => $this->paymentInsights($baseQuery),
            'filters' => $filters,
            'counts' => $this->statusCounts($baseQuery, ['posted', 'pending', 'void'], $filters),
            'portfolioOptions' => $this->portfolioOptions($actor),
            'leaseOptions' => $this->scopeByPortfolio(
                Lease::query()
                    ->with(['tenantProfile.user', 'leaseable', 'installments'])
                    ->where('status', 'active'),
                $actor
            )->get()->map(fn (Lease $lease) => [
                'id' => $lease->id,
                'portfolio_id' => $lease->portfolio_id,
                'tenant_profile_id' => $lease->tenant_profile_id,
                'code' => $lease->code,
                'currency' => $lease->currency,
                'balance_remaining' => (float) $lease->balance_remaining,
                'total_due' => (float) $lease->total_due,
                'total_paid' => (float) $lease->total_paid,
                'tenant_profile' => [
                    'user' => [
                        'name' => $lease->tenantProfile?->user?->name,
                    ],
                ],
                'leaseable' => [
                    'title_en' => $lease->leaseable?->title_en,
                    'code' => $lease->leaseable?->code,
                ],
            ])->values(),
            'tenantOptions' => $this->scopeByPortfolio(
                TenantProfile::query()->with('user'),
                $actor
            )->get(),
        ]);
    }

    public function create(Request $request): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->paymentFormPage($actor),
        ]);
    }

    public function show(Request $request, Payment $payment): Response
    {
        $actor = $this->actor($request);
        $this->ensurePaymentReceiptAccess($actor, $payment);
        $payment->loadMissing([
            'portfolio',
            'lease.leaseable',
            'lease.installments',
            'tenantProfile.user',
            'recordedBy',
            'allocations.leaseInstallment',
            'documents',
        ]);
        $adminMode = $actor->hasAnyRole(['superadmin', 'owner', 'property_manager']);

        return Inertia::render('admin/resource-show', [
            'detailPage' => [
                'header' => [
                    'eyebrow' => 'Payment detail',
                    'title' => $payment->reference ?: 'Payment #'.$payment->id,
                    'description' => trim($payment->status.' · '.$payment->method.' · '.$payment->type),
                    'backHref' => $adminMode ? route('payments.index') : route('dashboard'),
                    'backLabel' => $adminMode ? 'All payments' : 'Dashboard',
                    'actions' => array_values(array_filter([
                        $adminMode ? ['label' => 'Review payment', 'href' => route('payments.edit', $payment), 'variant' => 'primary'] : null,
                        $payment->status === 'posted' ? ['label' => 'Download receipt', 'href' => route('payments.receipt', $payment), 'variant' => 'secondary'] : null,
                        ['label' => 'Open lease', 'href' => route('leases.show', $payment->lease), 'variant' => 'secondary'],
                    ])),
                ],
                'stats' => $this->detailItems([
                    ['label' => 'Amount', 'value' => number_format((float) $payment->amount, 2).' '.$payment->currency, 'tone' => 'primary'],
                    ['label' => 'Allocated', 'value' => number_format((float) $payment->allocated_amount, 2).' '.$payment->currency, 'tone' => 'teal'],
                    ['label' => 'Unallocated', 'value' => number_format((float) $payment->unallocated_amount, 2).' '.$payment->currency],
                    ['label' => 'Status', 'value' => $payment->status, 'tone' => $payment->status === 'void' ? 'danger' : 'teal'],
                ]),
                'sections' => [
                    [
                        'title' => 'Payment record',
                        'description' => 'Manual payment tracking and receipt context.',
                        'items' => $this->detailItems([
                            ['label' => 'Tenant', 'value' => $payment->tenantProfile?->user?->name, 'href' => $payment->tenantProfile ? route('tenants.show', $payment->tenantProfile) : null],
                            ['label' => 'Lease', 'value' => $payment->lease?->code, 'href' => $payment->lease ? route('leases.show', $payment->lease) : null],
                            ['label' => 'Asset', 'value' => $payment->lease?->leaseable?->title_en, 'href' => $payment->lease?->leaseable ? route('assets.show', $payment->lease->leaseable) : null],
                            ['label' => 'Portfolio', 'value' => $payment->portfolio?->name_en, 'href' => $payment->portfolio ? route('portfolios.show', $payment->portfolio) : null],
                            ['label' => 'Recorded by', 'value' => $payment->recordedBy?->name, 'href' => $payment->recordedBy ? route('users.show', $payment->recordedBy) : null],
                            ['label' => 'Received on', 'value' => $payment->received_on?->toDateString()],
                            ['label' => 'Notes', 'value' => $payment->notes],
                        ]),
                    ],
                ],
                'related' => [
                    [
                        'title' => 'Allocations',
                        'description' => 'Installments this payment touched.',
                        'columns' => ['Installment', 'Due date', 'Amount'],
                        'rows' => $payment->allocations->map(fn ($allocation) => [
                            'Installment' => '#'.$allocation->leaseInstallment?->sequence,
                            'Due date' => $allocation->leaseInstallment?->due_date?->toDateString(),
                            'Amount' => number_format((float) $allocation->amount, 2).' '.$payment->currency,
                        ])->all(),
                        'emptyText' => 'No allocations yet. Pending and void payments do not allocate.',
                    ],
                ],
                'documents' => $this->documentStrip($payment->documents),
                'timeline' => $this->activityTimeline($payment),
            ],
        ]);
    }

    public function edit(Request $request, Payment $payment): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $payment->portfolio_id);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->paymentFormPage($actor, $payment),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        $data = $request->validate([
            'portfolio_id' => ['nullable', 'integer', 'exists:portfolios,id'],
            'lease_id' => ['required', 'integer', 'exists:leases,id'],
            'tenant_profile_id' => ['nullable', 'integer', 'exists:tenant_profiles,id'],
            'type' => ['required', Rule::in(['rent', 'deposit', 'fee'])],
            'method' => ['required', Rule::in(['bank_transfer', 'cash', 'card'])],
            'status' => ['required', Rule::in(['posted', 'pending'])],
            'reference' => ['nullable', 'string', 'max:255', 'unique:payments,reference'],
            'received_on' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
        ]);

        $lease = Lease::query()->findOrFail($data['lease_id']);
        $portfolioId = $data['portfolio_id'] ?? $lease->portfolio_id;
        $this->ensurePortfolioAccess($actor, $portfolioId);
        abort_if($lease->portfolio_id !== $portfolioId, 422, 'Lease does not belong to the selected portfolio.');

        $tenantProfileId = $data['tenant_profile_id'] ?? $lease->tenant_profile_id;
        abort_unless(
            TenantProfile::query()
                ->whereKey($tenantProfileId)
                ->where('portfolio_id', $portfolioId)
                ->exists(),
            422,
            'Tenant does not belong to the selected portfolio.'
        );
        abort_if(
            (int) $tenantProfileId !== (int) $lease->tenant_profile_id,
            422,
            'Payment tenant must match the selected lease tenant.'
        );

        $payment = Payment::query()->create([
            'portfolio_id' => $portfolioId,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $tenantProfileId,
            'recorded_by_user_id' => $actor->id,
            'reference' => $data['reference'] ?? null,
            'type' => $data['type'],
            'method' => $data['method'],
            'status' => $data['status'],
            'received_on' => $data['received_on'],
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? $lease->currency,
            'notes' => $data['notes'] ?? null,
        ]);

        if ($payment->status === 'posted') {
            $this->leaseFinancials->allocatePayment($payment);
        }

        return to_route('payments.show', $payment)->with('success', 'Payment recorded successfully.');
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $payment->portfolio_id);

        $data = $request->validate([
            'status' => ['required', Rule::in(['posted', 'pending', 'void'])],
            'notes' => ['nullable', 'string'],
        ]);

        if ($payment->status === 'void' && $data['status'] !== 'void') {
            return back()->with('error', 'Voided payments cannot be reopened. Record a new payment instead.');
        }

        DB::transaction(function () use ($payment, $data) {
            $originalStatus = $payment->status;

            if ($data['status'] === 'void' && $originalStatus !== 'void') {
                $this->leaseFinancials->voidPayment($payment);
                $payment->update(['notes' => $data['notes'] ?? null]);

                return;
            }

            if ($originalStatus === 'posted' && $data['status'] === 'pending') {
                $this->leaseFinancials->reverseAllocations($payment);
            }

            $payment->update($data);

            if ($originalStatus !== 'posted' && $data['status'] === 'posted') {
                $this->leaseFinancials->allocatePayment($payment->fresh());
            }
        });

        return to_route('payments.show', $payment)->with('success', 'Payment updated successfully.');
    }

    public function destroy(Request $request, Payment $payment): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $payment->portfolio_id);

        DB::transaction(fn () => $this->leaseFinancials->voidPayment($payment));

        return to_route('payments.index')->with('success', 'Payment voided and allocations reversed.');
    }

    public function receipt(Request $request, Payment $payment): StreamedResponse
    {
        $actor = $this->actor($request);
        $this->ensurePaymentReceiptAccess($actor, $payment);
        $payment->loadMissing('lease', 'tenantProfile.user', 'recordedBy', 'allocations.leaseInstallment');

        $pdf = Pdf::loadView('pdf.receipt', ['payment' => $payment]);
        $reference = $payment->reference ?: (string) $payment->id;

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            "receipt-{$reference}.pdf"
        );
    }

    private function paymentFormPage(User $actor, ?Payment $payment = null): array
    {
        if ($payment) {
            return [
                'title' => 'Review payment '.($payment->reference ?: '#'.$payment->id),
                'description' => 'Change payment state carefully. Voiding reverses allocations and keeps the audit trail.',
                'backHref' => route('payments.show', $payment),
                'backLabel' => 'Payment detail',
                'action' => route('payments.update', $payment),
                'method' => 'put',
                'submitLabel' => 'Update payment',
                'fields' => [
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $this->fieldOptions(['posted', 'pending', 'void'])],
                    ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
                ],
                'initialValues' => [
                    'status' => $payment->status,
                    'notes' => $payment->notes ?? '',
                ],
            ];
        }

        $leaseOptions = $this->scopeByPortfolio(
            Lease::query()->with(['tenantProfile.user', 'leaseable', 'installments'])->where('status', 'active'),
            $actor
        )->get()->map(fn (Lease $lease) => [
            'value' => $lease->id,
            'label' => $lease->code.' · '.($lease->tenantProfile?->user?->name ?? 'tenant').' · '.($lease->leaseable?->title_en ?? 'asset'),
        ])->all();
        $tenantOptions = $this->scopeByPortfolio(TenantProfile::query()->with('user'), $actor)
            ->get()
            ->map(fn (TenantProfile $tenant) => ['value' => $tenant->id, 'label' => $tenant->user?->name ?? 'Tenant #'.$tenant->id])
            ->prepend(['value' => '', 'label' => 'Use selected lease tenant'])
            ->values()
            ->all();
        $fields = [];

        if ($actor->hasRole('superadmin')) {
            $fields[] = [
                'name' => 'portfolio_id',
                'label' => 'Portfolio',
                'type' => 'select',
                'options' => collect($this->portfolioOptions($actor))->map(fn ($portfolio) => ['value' => $portfolio['id'], 'label' => $portfolio['name']])->all(),
            ];
        }

        $fields = [
            ...$fields,
            ['name' => 'lease_id', 'label' => 'Lease', 'type' => 'select', 'required' => true, 'options' => $leaseOptions],
            ['name' => 'tenant_profile_id', 'label' => 'Tenant override', 'type' => 'select', 'options' => $tenantOptions],
            ['name' => 'type', 'label' => 'Payment type', 'type' => 'select', 'options' => $this->fieldOptions(['rent', 'deposit', 'fee'])],
            ['name' => 'method', 'label' => 'Method', 'type' => 'select', 'options' => $this->fieldOptions(['bank_transfer', 'cash', 'card'])],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $this->fieldOptions(['posted', 'pending'])],
            ['name' => 'reference', 'label' => 'Reference'],
            ['name' => 'received_on', 'label' => 'Received on', 'type' => 'date', 'required' => true],
            ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'min' => 0.01, 'required' => true],
            ['name' => 'currency', 'label' => 'Currency'],
            ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
        ];

        return [
            'title' => 'Record payment',
            'description' => 'Post manual rent, deposit, or fees against an active lease.',
            'backHref' => route('payments.index'),
            'backLabel' => 'All payments',
            'action' => route('payments.store'),
            'method' => 'post',
            'submitLabel' => 'Record payment',
            'fields' => $fields,
            'initialValues' => [
                'portfolio_id' => (string) request('portfolio_id', $actor->portfolio_id ?? $this->portfolioOptions($actor)[0]['id'] ?? ''),
                'lease_id' => (string) request('lease_id', $leaseOptions[0]['value'] ?? ''),
                'tenant_profile_id' => (string) request('tenant_profile_id', ''),
                'type' => 'rent',
                'method' => 'bank_transfer',
                'status' => 'posted',
                'reference' => '',
                'received_on' => now()->toDateString(),
                'amount' => 0,
                'currency' => 'SAR',
                'notes' => '',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentTableRow(Payment $payment): array
    {
        $payment->loadMissing([
            'lease.installments',
            'lease.leaseable',
            'tenantProfile.user',
            'allocations.leaseInstallment',
        ]);

        $allocatedAmount = (float) $payment->allocations->sum('amount');

        return [
            'id' => $payment->id,
            'portfolio_id' => $payment->portfolio_id,
            'lease_id' => $payment->lease_id,
            'tenant_profile_id' => $payment->tenant_profile_id,
            'reference' => $payment->reference,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'received_on' => $payment->received_on?->toDateString(),
            'status' => $payment->status,
            'type' => $payment->type,
            'method' => $payment->method,
            'notes' => $payment->notes,
            'allocated_amount' => $allocatedAmount,
            'unallocated_amount' => max(0, (float) $payment->amount - $allocatedAmount),
            'allocation_count' => $payment->allocations->count(),
            'receipt_url' => route('payments.receipt', $payment),
            'tenant_profile' => [
                'id' => $payment->tenantProfile?->id,
                'user' => [
                    'name' => $payment->tenantProfile?->user?->name,
                    'email' => $payment->tenantProfile?->user?->email,
                ],
            ],
            'lease' => [
                'id' => $payment->lease?->id,
                'code' => $payment->lease?->code,
                'status' => $payment->lease?->status,
                'balance_remaining' => $payment->lease ? (float) $payment->lease->balance_remaining : null,
                'total_due' => $payment->lease ? (float) $payment->lease->total_due : null,
                'total_paid' => $payment->lease ? (float) $payment->lease->total_paid : null,
                'leaseable' => [
                    'title_en' => $payment->lease?->leaseable?->title_en,
                    'code' => $payment->lease?->leaseable?->code,
                ],
            ],
            'allocations' => $payment->allocations->map(fn ($allocation) => [
                'id' => $allocation->id,
                'amount' => (float) $allocation->amount,
                'allocation_type' => $allocation->allocation_type,
                'installment' => [
                    'id' => $allocation->leaseInstallment?->id,
                    'label' => $allocation->leaseInstallment?->label,
                    'due_date' => $allocation->leaseInstallment?->due_date?->toDateString(),
                ],
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, int|float>
     */
    private function paymentInsights(Builder $baseQuery): array
    {
        $payments = (clone $baseQuery)
            ->with('allocations')
            ->get();

        $posted = $payments->where('status', 'posted');
        $pending = $payments->where('status', 'pending');
        $void = $payments->where('status', 'void');

        return [
            'total' => $payments->count(),
            'posted_count' => $posted->count(),
            'pending_count' => $pending->count(),
            'void_count' => $void->count(),
            'posted_amount' => (float) $posted->sum('amount'),
            'pending_amount' => (float) $pending->sum('amount'),
            'void_amount' => (float) $void->sum('amount'),
            'allocated_amount' => (float) $posted->sum(fn (Payment $payment) => $payment->allocations->sum('amount')),
            'unallocated_amount' => (float) $posted->sum(fn (Payment $payment) => max(0, (float) $payment->amount - (float) $payment->allocations->sum('amount'))),
            'received_this_month' => (float) $posted
                ->filter(fn (Payment $payment) => $payment->received_on?->isSameMonth(now()) ?? false)
                ->sum('amount'),
        ];
    }

    private function ensurePaymentReceiptAccess(User $actor, Payment $payment): void
    {
        if ($actor->hasAnyRole(['superadmin', 'owner', 'property_manager'])) {
            $this->ensurePortfolioAccess($actor, $payment->portfolio_id);

            return;
        }

        abort_unless(
            $actor->hasRole('tenant') && $payment->tenantProfile?->user_id === $actor->id,
            403,
            'You are not allowed to access this receipt.'
        );
    }
}
