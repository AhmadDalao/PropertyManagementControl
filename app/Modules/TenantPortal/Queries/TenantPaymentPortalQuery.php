<?php

namespace App\Modules\TenantPortal\Queries;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Payments\Presenters\PaymentTableRowPresenter;
use App\Modules\TenantPortal\Support\TenantPortalAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class TenantPaymentPortalQuery
{
    public function __construct(
        private TenantPortalAccess $access,
        private PaymentTableRowPresenter $rows,
    ) {}

    /**
     * @param  array{search:string,status:string,type:string,lease_id:?int,date_from:string,date_to:string,per_page:int}  $filters
     * @return array<string, mixed>
     */
    public function handle(User $actor, array $filters): array
    {
        $profile = $this->access->profile($actor);
        /** @var Builder<Payment> $base */
        $base = Payment::query()
            ->when(
                $profile,
                fn (Builder $query) => $query->where('tenant_profile_id', $profile->id),
                fn (Builder $query) => $query->whereRaw('1 = 0'),
            );
        $query = $this->filter(clone $base, $filters)
            ->with(['lease.leaseable', 'tenantProfile.user'])
            ->withCount('allocations')
            ->withSum('allocations', 'amount')
            ->latest('received_on')
            ->latest('id');

        /** @var LengthAwarePaginator<int, Payment> $payments */
        $payments = $query->paginate($filters['per_page'])->withQueryString();
        $payments->through(fn (Payment $payment): array => $this->rows->present($payment));

        /** @var Collection<int, Lease> $leases */
        $leases = $profile
            ? Lease::query()->where('tenant_profile_id', $profile->id)->with('leaseable')->latest('ends_at')->get()
            : new Collection;

        return [
            'filters' => $filters,
            'payments' => $payments,
            'counts' => [
                'all' => (clone $base)->count(),
                'posted' => (clone $base)->where('status', 'posted')->count(),
                'pending' => (clone $base)->where('status', 'pending')->count(),
                'void' => (clone $base)->where('status', 'void')->count(),
            ],
            'financials' => $this->financials($profile?->id),
            'leases' => $leases->map(fn (Lease $lease): array => $this->leaseOption($lease))->all(),
        ];
    }

    /**
     * @param  Builder<Payment>  $query
     * @param  array{search:string,status:string,type:string,lease_id:?int,date_from:string,date_to:string,per_page:int}  $filters
     * @return Builder<Payment>
     */
    private function filter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['status'] !== 'all', fn (Builder $copy) => $copy->where('status', $filters['status']))
            ->when($filters['lease_id'], fn (Builder $copy) => $copy->where('lease_id', $filters['lease_id']))
            ->when($filters['date_from'] !== '', fn (Builder $copy) => $copy->whereDate('received_on', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn (Builder $copy) => $copy->whereDate('received_on', '<=', $filters['date_to']))
            ->when($filters['search'] !== '', function (Builder $copy) use ($filters): void {
                $term = '%'.addcslashes($filters['search'], '%_\\').'%';
                $copy->where(function (Builder $search) use ($term): void {
                    $search
                        ->where('reference', 'like', $term)
                        ->orWhere('type', 'like', $term)
                        ->orWhere('method', 'like', $term)
                        ->orWhereHas('lease', fn (Builder $lease) => $lease->where('code', 'like', $term));
                });
            });
    }

    /** @return array<int, array{currency:string,scheduled:float,paid:float,outstanding:float,overdue:float}> */
    private function financials(?int $profileId): array
    {
        if (! $profileId) {
            return [];
        }

        return Lease::query()
            ->where('tenant_profile_id', $profileId)
            ->withSum('installments as scheduled_total', 'amount_due')
            ->withSum('installments as paid_total', 'amount_paid')
            ->withSum([
                'installments as overdue_due' => fn (Builder $query) => $query->whereDate('due_date', '<', today()),
            ], 'amount_due')
            ->withSum([
                'installments as overdue_paid' => fn (Builder $query) => $query->whereDate('due_date', '<', today()),
            ], 'amount_paid')
            ->get()
            ->groupBy('currency')
            ->map(function ($leases, string $currency): array {
                $scheduled = (float) $leases->sum('scheduled_total');
                $paid = (float) $leases->sum('paid_total');

                return [
                    'currency' => $currency,
                    'scheduled' => $scheduled,
                    'paid' => $paid,
                    'outstanding' => max(0, $scheduled - $paid),
                    'overdue' => max(0, (float) $leases->sum('overdue_due') - (float) $leases->sum('overdue_paid')),
                ];
            })
            ->values()
            ->all();
    }

    /** @return array{id:int,code:string,status:string,asset_title_en:?string,asset_title_ar:?string} */
    private function leaseOption(Lease $lease): array
    {
        $asset = $lease->leaseable instanceof Asset ? $lease->leaseable : null;

        return [
            'id' => $lease->id,
            'code' => $lease->code,
            'status' => $lease->status,
            'asset_title_en' => $asset?->title_en,
            'asset_title_ar' => $asset?->title_ar,
        ];
    }
}
