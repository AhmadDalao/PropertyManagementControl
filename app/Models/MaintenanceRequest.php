<?php

namespace App\Models;

use App\Models\Concerns\HasShowcaseBadge;
use App\Models\Concerns\LogsModelActivity;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Portfolio|null $portfolio
 * @property-read Asset|null $asset
 * @property-read Lease|null $lease
 * @property-read TenantProfile|null $tenantProfile
 * @property-read User|null $submittedBy
 * @property-read User|null $assignedTo
 * @property-read Collection<int, MaintenanceUpdate> $updates
 * @property-read Collection<int, ExpenseEntry> $expenses
 * @property CarbonInterface|null $requested_at
 * @property CarbonInterface|null $due_at
 * @property CarbonInterface|null $resolved_at
 */
class MaintenanceRequest extends Model
{
    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    use HasShowcaseBadge;
    use LogsModelActivity;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'due_at' => 'datetime',
            'resolved_at' => 'datetime',
            'meta_json' => 'array',
        ];
    }

    /** @return BelongsTo<Portfolio, $this> */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    /** @return BelongsTo<Asset, $this> */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /** @return BelongsTo<Lease, $this> */
    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    /** @return BelongsTo<TenantProfile, $this> */
    public function tenantProfile(): BelongsTo
    {
        return $this->belongsTo(TenantProfile::class);
    }

    /** @return BelongsTo<User, $this> */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /** @return HasMany<MaintenanceUpdate, $this> */
    public function updates(): HasMany
    {
        return $this->hasMany(MaintenanceUpdate::class);
    }

    /** @return HasMany<ExpenseEntry, $this> */
    public function expenses(): HasMany
    {
        return $this->hasMany(ExpenseEntry::class);
    }
}
