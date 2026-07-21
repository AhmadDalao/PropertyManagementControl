<?php

namespace App\Modules\Payments\Queries;

use App\Models\Payment;
use App\Models\User;
use App\Modules\Search\Presenters\SearchResultPresenter;
use App\Modules\Search\Support\ModuleSearchSource;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;

class PaymentSearch extends ModuleSearchSource
{
    public function __construct(
        private readonly PortfolioScope $portfolios,
        private readonly TableQuery $tables,
        private readonly SearchResultPresenter $results,
    ) {}

    public function results(User $actor, string $query): array
    {
        if (! $this->supports($actor)) {
            return [];
        }

        $payments = $this->query($actor)->with(['tenantProfile.user', 'lease.leaseable']);
        $this->tables->search($payments, $query, [
            'reference',
            'notes',
            fn (Builder $payments, string $term, string $like) => $payments->orWhereHas(
                'tenantProfile.user',
                fn (Builder $users) => $users
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like),
            ),
            fn (Builder $payments, string $term, string $like) => $payments->orWhereHas(
                'lease',
                fn (Builder $leases) => $leases->where('code', 'like', $like),
            ),
        ]);

        return $payments
            ->limit(5)
            ->get()
            ->map(fn (Payment $payment): array => $this->results->result(
                $actor->hasRole('tenant') ? trans('app.search.my_payments') : trans('app.nav.payments'),
                $payment->reference ?: '#'.$payment->id,
                data_get($payment, 'tenantProfile.user.name') ?: $payment->lease?->code,
                $this->results->status($payment->status),
                route('payments.show', $payment),
            ))
            ->all();
    }

    public function directUrl(User $actor, string $query): ?string
    {
        if (! $this->supports($actor)) {
            return null;
        }

        $payment = $this->query($actor)->where('reference', $query)->first();

        return $payment ? route('payments.show', $payment) : null;
    }

    private function supports(User $actor): bool
    {
        return ($this->isManager($actor) || $actor->hasRole('tenant'))
            && $this->moduleEnabled($actor, 'payments');
    }

    /** @return Builder<Payment> */
    private function query(User $actor): Builder
    {
        if ($actor->hasRole('tenant')) {
            return Payment::query()->whereHas(
                'tenantProfile',
                fn (Builder $tenants) => $tenants->where('user_id', $actor->id),
            );
        }

        return $this->portfolios->apply(Payment::query(), $actor);
    }
}
