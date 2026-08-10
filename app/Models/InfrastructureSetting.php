<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property bool $mail_enabled
 * @property string|null $mail_host
 * @property int|null $mail_port
 * @property string|null $mail_scheme
 * @property string|null $mail_username
 * @property string|null $mail_password
 * @property string|null $mail_from_address
 * @property string|null $mail_from_name
 * @property string|null $scheduler_php_binary
 * @property int|null $updated_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $updatedBy
 */
final class InfrastructureSetting extends Model
{
    protected $guarded = [];

    protected $hidden = ['mail_password'];

    protected $attributes = [
        'mail_enabled' => false,
        'mail_port' => 465,
        'mail_scheme' => 'smtps',
    ];

    protected function casts(): array
    {
        return [
            'mail_enabled' => 'boolean',
            'mail_port' => 'integer',
            'mail_password' => 'encrypted',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
