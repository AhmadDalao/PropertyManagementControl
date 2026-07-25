<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Portfolio|null $portfolio
 * @property-read Lease|null $lease
 * @property-read LeaseInstallment|null $installment
 * @property-read User|null $recordedBy
 * @property-read User|null $assignedTo
 * @property CarbonInterface|null $contacted_at
 * @property string|null $outstanding_amount_at_contact
 * @property string|null $promised_amount
 * @property CarbonInterface|null $promised_on
 * @property CarbonInterface|null $next_follow_up_on
 */
class CollectionFollowUp extends Model
{
    use LogsModelActivity;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'contacted_at' => 'datetime',
            'outstanding_amount_at_contact' => 'decimal:2',
            'promised_amount' => 'decimal:2',
            'promised_on' => 'date',
            'next_follow_up_on' => 'date',
        ];
    }

    /** @return BelongsTo<Portfolio, $this> */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    /** @return BelongsTo<Lease, $this> */
    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    /** @return BelongsTo<LeaseInstallment, $this> */
    public function installment(): BelongsTo
    {
        return $this->belongsTo(LeaseInstallment::class, 'lease_installment_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }
}
