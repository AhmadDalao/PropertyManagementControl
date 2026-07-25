<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Lease|null $lease
 * @property-read Portfolio|null $portfolio
 * @property-read User|null $initiatedBy
 * @property-read User|null $completedBy
 * @property CarbonInterface|null $move_out_date
 * @property CarbonInterface|null $completed_at
 * @property CarbonInterface|null $cancelled_at
 */
class LeaseMoveOut extends Model
{
    use LogsModelActivity;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'move_out_date' => 'date',
            'keys_returned' => 'boolean',
            'deposit_deduction_amount' => 'decimal:2',
            'balance_at_completion' => 'decimal:2',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Portfolio, $this> */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    /** @return BelongsTo<Lease, $this> */
    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    /** @return BelongsTo<User, $this> */
    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }
}
