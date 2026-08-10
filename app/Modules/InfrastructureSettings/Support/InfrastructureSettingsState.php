<?php

namespace App\Modules\InfrastructureSettings\Support;

use App\Models\InfrastructureSetting;

final class InfrastructureSettingsState
{
    public function smtpReady(?InfrastructureSetting $settings): bool
    {
        return $settings?->mail_enabled === true
            && $this->filled($settings->mail_host)
            && $settings->mail_port !== null
            && in_array($settings->mail_scheme, ['smtp', 'smtps'], true)
            && $this->filled($settings->mail_username)
            && $this->filled($settings->mail_password)
            && $this->filled($settings->mail_from_address)
            && $this->filled($settings->mail_from_name);
    }

    private function filled(?string $value): bool
    {
        return trim((string) $value) !== '';
    }
}
