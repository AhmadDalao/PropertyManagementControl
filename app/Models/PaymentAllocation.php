<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Payment|null $payment
 * @property-read LeaseInstallment|null $leaseInstallment
 */
class PaymentAllocation extends Model
{
    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    use LogsModelActivity;

    protected $guarded = [];

    /** @return BelongsTo<Payment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /** @return BelongsTo<LeaseInstallment, $this> */
    public function leaseInstallment(): BelongsTo
    {
        return $this->belongsTo(LeaseInstallment::class);
    }
}
