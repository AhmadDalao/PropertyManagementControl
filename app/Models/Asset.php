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

/**
 * @property-read Portfolio|null $portfolio
 * @property-read Asset|null $parent
 * @property-read Collection<int, Asset> $children
 * @property-read Collection<int, AssetStakeholder> $stakeholders
 * @property-read Collection<int, Lease> $leases
 * @property-read Collection<int, Document> $documents
 * @property-read Collection<int, MaintenanceRequest> $maintenanceRequests
 * @property-read Collection<int, ExpenseEntry> $expenses
 */
class Asset extends Model
{
    use HasFactory;
    use HasShowcaseBadge;
    use LogsModelActivity;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'rentable' => 'boolean',
            'meta_json' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Portfolio, $this>
     */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Asset, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * @return HasMany<AssetStakeholder, $this>
     */
    public function stakeholders(): HasMany
    {
        return $this->hasMany(AssetStakeholder::class);
    }

    /**
     * @return MorphMany<Lease, $this>
     */
    public function leases(): MorphMany
    {
        return $this->morphMany(Lease::class, 'leaseable');
    }

    /**
     * @return MorphMany<Document, $this>
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * @return HasMany<MaintenanceRequest, $this>
     */
    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    /**
     * @return HasMany<ExpenseEntry, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(ExpenseEntry::class);
    }
}
