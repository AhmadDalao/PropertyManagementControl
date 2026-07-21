<?php

namespace App\Models;

use App\Models\Concerns\HasShowcaseBadge;
use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $owner_user_id
 * @property string $name_en
 * @property string $name_ar
 * @property string $code
 * @property string $slug
 * @property string $status
 * @property string $default_currency
 * @property-read User|null $owner
 * @property-read ShowcaseDataset|null $showcaseDataset
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, Asset> $assets
 * @property-read Collection<int, TenantProfile> $tenantProfiles
 * @property-read Collection<int, Lease> $leases
 * @property-read Collection<int, Payment> $payments
 * @property-read Collection<int, MaintenanceRequest> $maintenanceRequests
 * @property-read Collection<int, ExpenseEntry> $expenseEntries
 */
class Portfolio extends Model
{
    use HasFactory;
    use HasShowcaseBadge;
    use LogsModelActivity;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'module_settings' => 'array',
            'theme_settings' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /** @return BelongsTo<ShowcaseDataset, $this> */
    public function showcaseDataset(): BelongsTo
    {
        return $this->belongsTo(ShowcaseDataset::class);
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<Asset, $this> */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    /** @return HasMany<TenantProfile, $this> */
    public function tenantProfiles(): HasMany
    {
        return $this->hasMany(TenantProfile::class);
    }

    /** @return HasMany<Lease, $this> */
    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<MaintenanceRequest, $this> */
    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    /** @return HasMany<ExpenseEntry, $this> */
    public function expenseEntries(): HasMany
    {
        return $this->hasMany(ExpenseEntry::class);
    }
}
