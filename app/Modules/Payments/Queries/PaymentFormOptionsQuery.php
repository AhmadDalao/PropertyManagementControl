<?php

namespace App\Modules\Payments\Queries;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Payments\Data\PaymentFormData;
use App\Modules\Payments\Support\PaymentOptions;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\ResourcePresenter;
use Illuminate\Database\Eloquent\Builder;

final class PaymentFormOptionsQuery
{
    public function __construct(
        private readonly PortfolioScope $portfolioScope,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @param array<string, mixed> $defaults */
    public function get(User $actor, array $defaults = []): PaymentFormData
    {
        $portfolios = $this->activePortfolios($actor);
        $portfolioId = $this->portfolioId($actor, $portfolios, $defaults);

        return new PaymentFormData(
            actor: $actor,
            defaults: $defaults,
            portfolioId: $portfolioId,
            portfolios: $portfolios,
            leases: $portfolioId ? $this->payableLeases($portfolioId) : [],
        );
    }

    /** @return array<int, array{value:int,label:string}> */
    private function activePortfolios(User $actor): array
    {
        $nameColumn = app()->isLocale('ar') ? 'name_ar' : 'name_en';

        return $this->portfolioScope->apply(Portfolio::query(), $actor, 'id')
            ->where('status', 'active')
            ->orderBy($nameColumn)
            ->get(['id', 'name_en', 'name_ar', 'code'])
            ->map(fn (Portfolio $portfolio): array => [
                'value' => $portfolio->id,
                'label' => trim(($this->resources->localized($portfolio->name_en, $portfolio->name_ar) ?? '').' · '.$portfolio->code),
            ])->all();
    }

    /** @return array<int, array{value:int,label:string}> */
    private function payableLeases(int $portfolioId): array
    {
        return Lease::query()
            ->where('portfolio_id', $portfolioId)
            ->whereIn('status', PaymentOptions::PAYABLE_LEASE_STATUSES)
            ->whereHas('tenantProfile', fn (Builder $tenants) => $tenants->where('portfolio_id', $portfolioId))
            ->with([
                'tenantProfile:id,user_id',
                'tenantProfile.user:id,name',
                'leaseable',
            ])
            ->withSum('installments as installments_total_due', 'amount_due')
            ->withSum('installments as installments_total_paid', 'amount_paid')
            ->orderByDesc('started_at')
            ->orderBy('code')
            ->get([
                'id', 'portfolio_id', 'tenant_profile_id', 'leaseable_type', 'leaseable_id',
                'code', 'status', 'started_at', 'currency',
            ])
            ->map(function (Lease $lease): array {
                $asset = $lease->leaseable instanceof Asset ? $lease->leaseable : null;
                $due = (float) ($lease->getAttribute('installments_total_due') ?? 0);
                $paid = (float) ($lease->getAttribute('installments_total_paid') ?? 0);

                return [
                    'value' => $lease->id,
                    'label' => implode(' · ', array_filter([
                        $lease->code,
                        $lease->tenantProfile?->user?->name,
                        $this->resources->localized($asset?->title_en, $asset?->title_ar),
                        trans("app.status.{$lease->status}"),
                        trans('app.payments.balance_remaining', [
                            'amount' => number_format(max(0, $due - $paid), 2),
                            'currency' => $lease->currency,
                        ]),
                    ])),
                ];
            })->all();
    }

    /**
     * @param  array<int, array{value:int,label:string}>  $portfolios
     * @param  array<string, mixed>  $defaults
     */
    private function portfolioId(User $actor, array $portfolios, array $defaults): ?int
    {
        $ids = collect($portfolios)->pluck('value');
        $requestedPortfolio = filter_var($defaults['portfolio_id'] ?? null, FILTER_VALIDATE_INT);

        if ($requestedPortfolio && $ids->contains((int) $requestedPortfolio)) {
            return (int) $requestedPortfolio;
        }

        $requestedLease = filter_var($defaults['lease_id'] ?? null, FILTER_VALIDATE_INT);
        $leasePortfolio = $requestedLease
            ? Lease::query()
                ->whereKey((int) $requestedLease)
                ->whereIn('portfolio_id', $ids->all())
                ->whereIn('status', PaymentOptions::PAYABLE_LEASE_STATUSES)
                ->value('portfolio_id')
            : null;

        if ($leasePortfolio) {
            return (int) $leasePortfolio;
        }

        if ($actor->portfolio_id && $ids->contains($actor->portfolio_id)) {
            return (int) $actor->portfolio_id;
        }

        $candidate = Portfolio::query()
            ->whereIn('id', $ids->all())
            ->whereHas('leases', fn (Builder $leases) => $leases
                ->whereIn('status', PaymentOptions::PAYABLE_LEASE_STATUSES)
                ->whereHas('tenantProfile'))
            ->orderBy(app()->isLocale('ar') ? 'name_ar' : 'name_en')
            ->value('id');

        return $candidate ? (int) $candidate : ($portfolios[0]['value'] ?? null);
    }
}
