<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $notification_id
 * @property int|null $portfolio_id
 * @property int|null $user_id
 * @property string $notification_class
 * @property string $email_type
 * @property string $recipient_email
 * @property string|null $subject
 * @property string $status
 * @property string $mailer
 * @property string|null $transport_message_id
 * @property int $attempts
 * @property Carbon|null $started_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $failed_at
 * @property string|null $error_message
 * @property array<string, mixed>|null $meta_json
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Portfolio|null $portfolio
 * @property-read User|null $user
 */
class EmailDeliveryLog extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'started_at' => 'datetime',
            'accepted_at' => 'datetime',
            'failed_at' => 'datetime',
            'meta_json' => 'array',
        ];
    }

    /** @return BelongsTo<Portfolio, $this> */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
