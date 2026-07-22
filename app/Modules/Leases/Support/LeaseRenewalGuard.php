<?php

namespace App\Modules\Leases\Support;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Shared\MorphTypes;
use Illuminate\Validation\ValidationException;

final class LeaseRenewalGuard
{
    public function __construct(
        private readonly LeaseAccess $access,
        private readonly MorphTypes $morphTypes,
    ) {}

    public function sourceForForm(User $actor, Lease $target): Lease
    {
        $this->access->ensureCanManage($actor, $target);
        $source = Lease::query()
            ->with(['renewalLease', 'tenantProfile.user', 'leaseable'])
            ->whereKey($target->id)
            ->firstOrFail();
        $this->access->ensureCanManage($actor, $source);

        abort_if(
            ! in_array($source->status, ['active', 'expired'], true),
            409,
            trans('app.errors.lease_renewal_unavailable'),
        );
        abort_if(
            $source->renewalLease !== null,
            409,
            trans('app.errors.lease_renewal_exists'),
        );

        return $source;
    }

    /** @param array<string, mixed> $data */
    public function validateCreation(
        User $actor,
        array $data,
        int $portfolioId,
        TenantProfile $tenant,
        Asset $asset,
    ): ?Lease {
        $sourceId = filter_var(
            $data['renewed_from_lease_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );

        if ($sourceId === false) {
            return null;
        }

        $source = Lease::query()
            ->with('renewalLease')
            ->lockForUpdate()
            ->find($sourceId);

        if (! $source) {
            $this->fail('renewed_from_lease_id', 'app.errors.lease_renewal_unavailable');
        }

        $this->access->ensureCanManage($actor, $source);

        if (! in_array($source->status, ['active', 'expired'], true)) {
            $this->fail('renewed_from_lease_id', 'app.errors.lease_renewal_unavailable');
        }

        if ($source->renewalLease !== null) {
            $this->fail('renewed_from_lease_id', 'app.errors.lease_renewal_exists');
        }

        if (($data['status'] ?? null) !== 'draft') {
            $this->fail('status', 'app.errors.lease_renewal_must_be_draft');
        }

        $sameParticipants = $source->portfolio_id === $portfolioId
            && $source->tenant_profile_id === $tenant->id
            && $source->leaseable_id === $asset->id
            && in_array($source->leaseable_type, $this->morphTypes->for($asset), true);

        if (! $sameParticipants) {
            $this->fail('renewed_from_lease_id', 'app.errors.lease_renewal_participants_mismatch');
        }

        if ((string) ($data['started_at'] ?? '') <= $source->ends_at->toDateString()) {
            $this->fail('started_at', 'app.errors.lease_renewal_start_after_source');
        }

        return $source;
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => trans($message)]);
    }
}
