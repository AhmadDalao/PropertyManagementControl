<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read Lease|null $lease
 * @property-read Collection<int, PaymentAllocation> $allocations
 * @property-read Collection<int, CollectionFollowUp> $collectionFollowUps
 * @property-read CollectionFollowUp|null $latestCollectionFollowUp
 * @property CarbonInterface|null $period_start
 * @property CarbonInterface|null $period_end
 * @property CarbonInterface|null $due_date
 * @property CarbonInterface|null $paid_at
 * @property-read float $remaining_amount
 */
class LeaseInstallment extends Model
{
    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    use LogsModelActivity;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Lease, $this> */
    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    /** @return HasMany<PaymentAllocation, $this> */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /** @return HasMany<CollectionFollowUp, $this> */
    public function collectionFollowUps(): HasMany
    {
        return $this->hasMany(CollectionFollowUp::class);
    }

    /** @return HasOne<CollectionFollowUp, $this> */
    public function latestCollectionFollowUp(): HasOne
    {
        return $this->hasOne(CollectionFollowUp::class)->latestOfMany();
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->amount_due - (float) $this->amount_paid);
    }
}
