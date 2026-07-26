<?php

namespace App\Modules\Maintenance\Actions;

use App\Models\MaintenanceVendor;
use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Maintenance\Support\MaintenanceVendorAccess;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ManageMaintenanceVendors
{
    public function __construct(
        private readonly MaintenanceVendorAccess $access,
        private readonly PortfolioScope $portfolios,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): MaintenanceVendor
    {
        $this->access->ensureManager($actor);

        return DB::transaction(function () use ($actor, $data): MaintenanceVendor {
            $portfolio = $this->activePortfolio($actor, $data['portfolio_id'] ?? null);

            return MaintenanceVendor::query()->create([
                'portfolio_id' => $portfolio->id,
                ...$this->mutable($data),
            ]);
        }, attempts: 3);
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, MaintenanceVendor $vendor, array $data): MaintenanceVendor
    {
        $this->access->ensureCanAccess($actor, $vendor);

        return DB::transaction(function () use ($actor, $vendor, $data): MaintenanceVendor {
            $locked = MaintenanceVendor::query()->lockForUpdate()->findOrFail($vendor->id);
            $this->access->ensureCanAccess($actor, $locked);
            $locked->update($this->mutable($data));

            return $locked->refresh();
        }, attempts: 3);
    }

    public function archive(User $actor, MaintenanceVendor $vendor): MaintenanceVendor
    {
        $this->access->ensureCanAccess($actor, $vendor);
        $vendor->update(['status' => 'inactive']);

        return $vendor->refresh();
    }

    private function activePortfolio(User $actor, mixed $portfolioId): Portfolio
    {
        $id = filter_var(
            $portfolioId ?? $actor->portfolio_id,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );

        if (! $id) {
            throw ValidationException::withMessages([
                'portfolio_id' => trans('validation.required', [
                    'attribute' => trans('app.fields.portfolio'),
                ]),
            ]);
        }

        $this->portfolios->ensureAccess($actor, (int) $id);
        $portfolio = Portfolio::query()->lockForUpdate()->findOrFail((int) $id);

        if ($portfolio->status !== 'active') {
            throw ValidationException::withMessages([
                'portfolio_id' => trans('app.errors.maintenance_vendor_portfolio_inactive'),
            ]);
        }

        return $portfolio;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{
     *     name:string,contact_name:?string,phone:?string,email:?string,
     *     service_category:mixed,status:mixed,notes:?string
     * }
     */
    private function mutable(array $data): array
    {
        return [
            'name' => trim((string) $data['name']),
            'contact_name' => $this->optional($data['contact_name'] ?? null),
            'phone' => $this->optional($data['phone'] ?? null),
            'email' => $this->optional($data['email'] ?? null),
            'service_category' => $data['service_category'],
            'status' => $data['status'],
            'notes' => $this->optional($data['notes'] ?? null),
        ];
    }

    private function optional(mixed $value): ?string
    {
        $normalized = is_string($value) ? trim($value) : '';

        return $normalized !== '' ? $normalized : null;
    }
}
