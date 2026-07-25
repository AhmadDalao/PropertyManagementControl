<?php

namespace App\Modules\SystemReadiness\Actions;

use App\Models\User;
use App\Modules\SystemReadiness\Notifications\ReadinessTestNotification;
use App\Modules\SystemReadiness\Support\MailReadiness;
use App\Modules\SystemReadiness\Support\ReadinessAccess;
use RuntimeException;

final class SendReadinessTestEmail
{
    public function __construct(
        private readonly ReadinessAccess $access,
        private readonly MailReadiness $mail,
    ) {}

    public function handle(User $actor): void
    {
        $this->access->ensureSuperadmin($actor);

        if (! $this->mail->configured()) {
            throw new RuntimeException(trans('app.readiness.mail_not_configured'));
        }

        $actor->notify(
            (new ReadinessTestNotification)
                ->locale($actor->preferred_locale),
        );
    }
}
