<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Model;

class Lease extends Model
{
    use HasFactory;
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

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function tenantProfile(): BelongsTo
    {
        return $this->belongsTo(TenantProfile::class);
    }

    public function managedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'managed_by_user_id');
    }

    public function leaseable(): MorphTo
    {
        return $this->morphTo();
    }

    public function installments(): HasMany
    {
        return $this->hasMany(LeaseInstallment::class)->orderBy('sequence');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

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

    public function getDaysRemainingAttribute(): ?int
    {
        if (! $this->ends_at) {
            return null;
        }

        return now()->startOfDay()->diffInDays($this->ends_at, false);
    }
}
