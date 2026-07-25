<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $portfolio_id
 * @property int $maintenance_request_id
 * @property int|null $uploaded_by_user_id
 * @property string $disk
 * @property string $file_path
 * @property string $original_name
 * @property string $mime_type
 * @property int $file_size
 * @property int $width
 * @property int $height
 * @property-read Portfolio|null $portfolio
 * @property-read MaintenanceRequest|null $maintenanceRequest
 * @property-read User|null $uploadedBy
 */
class MaintenanceAttachment extends Model
{
    use LogsModelActivity;

    protected $guarded = [];

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

    /** @return BelongsTo<User, $this> */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
