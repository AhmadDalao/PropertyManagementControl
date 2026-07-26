<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $portfolio_id
 * @property string $name
 * @property string|null $contact_name
 * @property string|null $phone
 * @property string|null $email
 * @property string $service_category
 * @property string $status
 * @property string|null $notes
 * @property array<string, mixed>|null $meta_json
 * @property int $work_orders_count
 * @property int $active_work_orders_count
 * @property-read Portfolio|null $portfolio
 * @property-read Collection<int, MaintenanceWorkOrder> $workOrders
 */
class MaintenanceVendor extends Model
{
    use LogsModelActivity;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'meta_json' => 'array',
        ];
    }

    /** @return BelongsTo<Portfolio, $this> */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    /** @return HasMany<MaintenanceWorkOrder, $this> */
    public function workOrders(): HasMany
    {
        return $this->hasMany(MaintenanceWorkOrder::class, 'vendor_id');
    }
}
