<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class PaymentAllocation extends Model
{
    use HasFactory;
    use LogsModelActivity;

    protected $guarded = [];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function leaseInstallment(): BelongsTo
    {
        return $this->belongsTo(LeaseInstallment::class);
    }
}
