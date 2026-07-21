<?php

namespace App\Modules\Dashboard\Queries;

use App\Models\Document;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Leases\Support\LeaseOptions;
use Illuminate\Database\Eloquent\Collection;

class TenantDashboardQuery
{
    /**
     * @return array{
     *     profile:?TenantProfile,
     *     lease:?Lease,
     *     payments:Collection<int, Payment>,
     *     requests:Collection<int, MaintenanceRequest>,
     *     documents:Collection<int, Document>,
     *     requestCount:int
     * }
     */
    public function forUser(User $user): array
    {
        $profile = TenantProfile::query()->where('user_id', $user->id)->first();
        $lease = $profile?->leases()
            ->whereIn('status', ['active', 'draft'])
            ->with(['installments', 'leaseable'])
            ->orderByRaw("CASE status WHEN 'active' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();
        $payments = $profile?->payments()
            ->where('status', 'posted')
            ->latest('received_on')
            ->limit(8)
            ->get() ?? new Collection;
        $requests = $profile?->maintenanceRequests()
            ->latest()
            ->limit(8)
            ->get() ?? new Collection;
        $documents = $lease?->documents()
            ->where('is_public', true)
            ->whereIn('type', LeaseOptions::TENANT_DOCUMENT_TYPES)
            ->latest()
            ->get() ?? new Collection;

        return [
            'profile' => $profile,
            'lease' => $lease,
            'payments' => $payments,
            'requests' => $requests,
            'documents' => $documents,
            'requestCount' => $profile?->maintenanceRequests()->count() ?? 0,
        ];
    }
}
