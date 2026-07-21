<?php

namespace App\Models;

use App\Models\Concerns\HasShowcaseBadge;
use App\Models\Concerns\LogsModelActivity;
use App\Notifications\ResetPasswordNotification;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use HasShowcaseBadge;
    use LogsModelActivity;
    use Notifiable;

    protected string $guard_name = 'web';

    protected $fillable = [
        'portfolio_id',
        'showcase_dataset_id',
        'name',
        'email',
        'phone',
        'preferred_locale',
        'status',
        'avatar_path',
        'force_password_reset',
        'last_login_at',
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'force_password_reset' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function showcaseDataset(): BelongsTo
    {
        return $this->belongsTo(ShowcaseDataset::class);
    }

    public function portfoliosOwned(): HasMany
    {
        return $this->hasMany(Portfolio::class, 'owner_user_id');
    }

    public function tenantProfile(): HasOne
    {
        return $this->hasOne(TenantProfile::class);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification((string) $token));
    }

    public function recordedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'recorded_by_user_id');
    }

    public function submittedMaintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class, 'submitted_by_user_id');
    }

    public function assignedMaintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class, 'assigned_to_user_id');
    }

    public function uploadedDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'uploaded_by_user_id');
    }

    public function uploadedMedia(): HasMany
    {
        return $this->hasMany(MediaFile::class, 'uploaded_by_user_id');
    }

    public function canManagePortfolio(?int $portfolioId): bool
    {
        if ($this->hasRole('superadmin')) {
            return true;
        }

        return $portfolioId !== null && $this->portfolio_id === $portfolioId && $this->hasAnyRole(['owner', 'property_manager']);
    }

    public function isTenant(): bool
    {
        return $this->hasRole('tenant');
    }
}
