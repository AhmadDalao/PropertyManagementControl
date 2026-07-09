<?php

namespace App\Http\Controllers;

use App\Models\Lease;
use App\Models\Payment;
use App\Models\TenantProfile;
use App\Services\LeaseFinancialService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $payments = (clone $baseQuery)->with(['lease.leaseable', 'tenantProfile.user', 'allocations']);

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
            ], 'received_on'),
            'filters' => $filters,
            'counts' => $this->statusCounts($baseQuery, ['posted', 'pending', 'void'], $filters),
            'portfolioOptions' => $this->portfolioOptions($actor),
            'leaseOptions' => $this->scopeByPortfolio(
                Lease::query()->with('tenantProfile.user')->where('status', 'active'),
                $actor
            )->get(),
            'tenantOptions' => $this->scopeByPortfolio(
                TenantProfile::query()->with('user'),
                $actor
            )->get(),
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
            'type' => ['required', 'string'],
            'method' => ['required', 'string'],
            'status' => ['required', 'string'],
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

        $this->leaseFinancials->allocatePayment($payment);

        return to_route('payments.index')->with('success', 'Payment recorded successfully.');
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $payment->portfolio_id);

        $data = $request->validate([
            'status' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($payment->status === 'void' && $data['status'] !== 'void') {
            return back()->with('error', 'Voided payments cannot be reopened. Record a new payment instead.');
        }

        if ($data['status'] === 'void' && $payment->status !== 'void') {
            DB::transaction(function () use ($payment, $data) {
                $this->leaseFinancials->voidPayment($payment);
                $payment->update(['notes' => $data['notes'] ?? null]);
            });

            return to_route('payments.index')->with('success', 'Payment voided and allocations reversed.');
        }

        $payment->update($data);

        return to_route('payments.index')->with('success', 'Payment updated successfully.');
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
        $this->ensurePortfolioAccess($actor, $payment->portfolio_id);
        $payment->loadMissing('lease', 'tenantProfile.user', 'recordedBy', 'allocations.leaseInstallment');

        $pdf = Pdf::loadView('pdf.receipt', ['payment' => $payment]);
        $reference = $payment->reference ?: (string) $payment->id;

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            "receipt-{$reference}.pdf"
        );
    }
}
