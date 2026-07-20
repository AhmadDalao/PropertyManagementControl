<?php

namespace App\Models;

use App\Models\Concerns\HasShowcaseBadge;
use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function stakeholders(): HasMany
    {
        return $this->hasMany(AssetStakeholder::class);
    }

    public function leases(): MorphMany
    {
        return $this->morphMany(Lease::class, 'leaseable');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(ExpenseEntry::class);
    }
}
