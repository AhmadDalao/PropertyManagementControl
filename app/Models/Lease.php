<?php

namespace App\Models;

use App\Models\Concerns\HasShowcaseBadge;
use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property-read Portfolio|null $portfolio
 * @property-read TenantProfile|null $tenantProfile
 * @property-read User|null $managedBy
 * @property-read Model|null $leaseable
 * @property-read Collection<int, LeaseInstallment> $installments
 * @property-read Collection<int, Payment> $payments
 * @property-read Collection<int, Document> $documents
 */
class Lease extends Model
{
    use HasFactory;
    use HasShowcaseBadge;
    use LogsModelActivity;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'ends_at' => 'date',
            'signed_at' => 'date',
            'terms_json' => 'array',
        ];
    }

    /** @return BelongsTo<Portfolio, $this> */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    /** @return BelongsTo<TenantProfile, $this> */
    public function tenantProfile(): BelongsTo
    {
        return $this->belongsTo(TenantProfile::class);
    }

    /** @return BelongsTo<User, $this> */
    public function managedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'managed_by_user_id');
    }

    /** @return MorphTo<Model, $this> */
    public function leaseable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return HasMany<LeaseInstallment, $this> */
    public function installments(): HasMany
    {
        return $this->hasMany(LeaseInstallment::class)->orderBy('sequence');
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return MorphMany<Document, $this> */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function getTotalDueAttribute(): float
    {
        return (float) $this->installments->sum('amount_due');
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->installments->sum('amount_paid');
    }

    public function getBalanceRemainingAttribute(): float
    {
        return max(0, $this->total_due - $this->total_paid);
    }

    public function getDueNowAmountAttribute(): float
    {
        return $this->outstandingInstallmentAmount(
            fn (LeaseInstallment $installment): bool => $installment->due_date?->lessThanOrEqualTo(today()) ?? false,
        );
    }

    public function getOverdueAmountAttribute(): float
    {
        return $this->outstandingInstallmentAmount(
            fn (LeaseInstallment $installment): bool => $installment->due_date?->lessThan(today()) ?? false,
        );
    }

    public function getNextDueInstallmentAttribute(): ?LeaseInstallment
    {
        return $this->installments
            ->filter(fn (LeaseInstallment $installment): bool => $installment->remaining_amount > 0)
            ->sortBy('due_date')
            ->first();
    }

    public function getDaysRemainingAttribute(): ?int
    {
        if (! $this->ends_at) {
            return null;
        }

        return now()->startOfDay()->diffInDays($this->ends_at, false);
    }

    private function outstandingInstallmentAmount(callable $matches): float
    {
        return (float) $this->installments
            ->filter($matches)
            ->sum(fn (LeaseInstallment $installment): float => $installment->remaining_amount);
    }
}
