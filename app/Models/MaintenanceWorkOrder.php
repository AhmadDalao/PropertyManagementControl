<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $portfolio_id
 * @property int $maintenance_request_id
 * @property int|null $vendor_id
 * @property int $created_by_user_id
 * @property int|null $assigned_to_user_id
 * @property string $reference_code
 * @property string $vendor_name
 * @property string|null $vendor_phone
 * @property string $status
 * @property CarbonInterface|null $scheduled_at
 * @property CarbonInterface|null $completed_at
 * @property float|null $estimated_amount
 * @property float|null $final_amount
 * @property string $currency
 * @property string $scope
 * @property string|null $completion_notes
 * @property bool $tenant_access_required
 * @property array<string, mixed>|null $meta_json
 * @property-read Portfolio|null $portfolio
 * @property-read MaintenanceRequest|null $maintenanceRequest
 * @property-read MaintenanceVendor|null $vendor
 * @property-read User|null $createdBy
 * @property-read User|null $assignedTo
 */
class MaintenanceWorkOrder extends Model
{
    use LogsModelActivity;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
            'estimated_amount' => 'float',
            'final_amount' => 'float',
            'tenant_access_required' => 'boolean',
            'meta_json' => 'array',
        ];
    }

    /** @return BelongsTo<Portfolio, $this> */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    /** @return BelongsTo<MaintenanceRequest, $this> */
    public function maintenanceRequest(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    /** @return BelongsTo<MaintenanceVendor, $this> */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(MaintenanceVendor::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }
}
