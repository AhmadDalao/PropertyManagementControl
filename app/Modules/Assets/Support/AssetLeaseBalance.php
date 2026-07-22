<?php

namespace App\Modules\Assets\Support;

use App\Models\Lease;

class AssetLeaseBalance
{
    public function remaining(Lease $lease): float
    {
        $due = (float) ($lease->getAttribute('installments_due_total') ?? 0);
        $paid = (float) ($lease->getAttribute('installments_paid_total') ?? 0);

        return max(0, $due - $paid);
    }
}
