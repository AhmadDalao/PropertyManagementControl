<?php

namespace App\Modules\OpeningData\Actions;

use App\Models\User;
use App\Modules\OpeningData\Support\OpeningDataAccess;
use App\Modules\OpeningData\Support\OpeningDataPreviewStore;
use App\Modules\OpeningData\Support\OpeningDataReferenceValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CommitOpeningData
{
    public function __construct(
        private readonly OpeningDataAccess $access,
        private readonly OpeningDataPreviewStore $previews,
        private readonly OpeningDataReferenceValidator $references,
        private readonly ImportOpeningAssets $assets,
        private readonly ImportOpeningTenants $tenants,
        private readonly ImportOpeningLeases $leases,
        private readonly ImportOpeningPayments $payments,
    ) {}

    /**
     * @return array{assets:int,tenants:int,leases:int,payments:int,portfolio_id:int}
     */
    public function handle(User $actor, string $token): array
    {
        $manifest = $this->previews->load($actor, $token);
        $portfolio = $this->access->portfolio(
            $actor,
            (int) ($manifest['portfolio_id'] ?? 0),
        );
        $data = $manifest['data'] ?? null;

        if (! ($manifest['ready'] ?? false) || ! is_array($data)) {
            throw ValidationException::withMessages([
                'preview_token' => trans('app.opening_data.errors.preview_has_issues'),
            ]);
        }

        /** @var array<string, array<int, array<string, mixed>>> $data */
        if ($this->references->validate($portfolio, $data) !== []) {
            throw ValidationException::withMessages([
                'preview_token' => trans('app.opening_data.errors.data_changed'),
            ]);
        }

        $counts = DB::transaction(function () use ($actor, $portfolio, $data): array {
            $assets = $this->assets->handle(
                $actor,
                $portfolio,
                $data['Assets'] ?? [],
            );
            $tenants = $this->tenants->handle(
                $actor,
                $portfolio->id,
                $data['Tenants'] ?? [],
            );
            $leases = $this->leases->handle(
                $actor,
                $portfolio->id,
                $data['Leases'] ?? [],
                $assets,
                $tenants,
            );
            $this->payments->handle(
                $actor,
                $portfolio->id,
                $data['Payments'] ?? [],
                $leases,
            );
            $counts = [
                'assets' => count($data['Assets'] ?? []),
                'tenants' => count($data['Tenants'] ?? []),
                'leases' => count($data['Leases'] ?? []),
                'payments' => count($data['Payments'] ?? []),
                'portfolio_id' => $portfolio->id,
            ];

            activity('opening_data')
                ->causedBy($actor)
                ->performedOn($portfolio)
                ->event('opening_data_imported')
                ->withProperties($counts)
                ->log('opening_data_imported');

            return $counts;
        }, attempts: 1);

        $this->previews->delete($actor, $token);

        return $counts;
    }
}
