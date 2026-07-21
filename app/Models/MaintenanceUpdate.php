<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read MaintenanceRequest|null $maintenanceRequest
 * @property-read User|null $user
 */
class MaintenanceUpdate extends Model
{
    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    use LogsModelActivity;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_public_comment' => 'boolean',
        ];
    }

    /** @return BelongsTo<MaintenanceRequest, $this> */
    public function maintenanceRequest(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
