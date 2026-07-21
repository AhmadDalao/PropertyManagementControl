<?php

namespace App\Models;

use App\Models\Concerns\HasShowcaseBadge;
use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $owner_user_id
 * @property int|null $showcase_dataset_id
 * @property string $name_en
 * @property string $name_ar
 * @property string $code
 * @property string $slug
 * @property string $status
 * @property string $default_currency
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property string|null $city
 * @property string|null $country
 * @property string|null $address
 * @property string|null $address_ar
 * @property array<string, bool>|null $module_settings
 * @property array<string, mixed>|null $theme_settings
 * @property bool $is_showcase
 * @property int $assets_count
 * @property int $users_count
 * @property int $leases_count
 * @property int $active_leases_count
 * @property int $open_maintenance_count
 * @property float|null $valuation_total
 * @property float|null $posted_revenue_total
 * @property float|null $posted_expense_total
 * @property-read User|null $owner
 * @property-read ShowcaseDataset|null $showcaseDataset
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, Asset> $assets
 * @property-read Collection<int, TenantProfile> $tenantProfiles
 * @property-read Collection<int, Lease> $leases
 * @property-read Collection<int, Payment> $payments
 * @property-read Collection<int, MaintenanceRequest> $maintenanceRequests
 * @property-read Collection<int, ExpenseEntry> $expenseEntries
 * @property-read Collection<int, Document> $documents
 */
class Portfolio extends Model
{
    /** @use HasFactory<Factory<static>> */
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

    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
