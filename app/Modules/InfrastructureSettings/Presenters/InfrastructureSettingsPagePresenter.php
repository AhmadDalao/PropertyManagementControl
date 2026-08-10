<?php

namespace App\Modules\InfrastructureSettings\Presenters;

use App\Models\InfrastructureSetting;
use App\Models\User;
use App\Modules\InfrastructureSettings\Support\InfrastructureSettingsAccess;
use App\Modules\InfrastructureSettings\Support\InfrastructureSettingsState;
use App\Modules\SystemReadiness\Queries\SystemHealthQuery;

final readonly class InfrastructureSettingsPagePresenter
{
    public function __construct(
        private InfrastructureSettingsAccess $access,
        private InfrastructureSettingsState $state,
        private SystemHealthQuery $health,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $actor): array
    {
        $this->access->ensureSuperadmin($actor);
        $settings = InfrastructureSetting::query()
            ->with('updatedBy:id,name')
            ->firstOrNew();
        $checks = collect($this->health->checks())
            ->whereIn('key', ['mail', 'scheduler', 'queue'])
            ->values()
            ->all();
        $binary = $settings->scheduler_php_binary
            ?: (string) config('operations.scheduler_php_binary', PHP_BINARY);

        return [
            'settings' => [
                'mail_enabled' => $settings->mail_enabled,
                'mail_host' => $settings->mail_host ?? '',
                'mail_port' => $settings->mail_port ?? 465,
                'mail_scheme' => $settings->mail_scheme ?? 'smtps',
                'mail_username' => $settings->mail_username ?? '',
                'mail_from_address' => $settings->mail_from_address ?? '',
                'mail_from_name' => $settings->mail_from_name ?? config('app.name'),
                'password_configured' => (bool) $settings->mail_password,
                'smtp_ready' => $this->state->smtpReady($settings),
                'scheduler_php_binary' => $binary,
                'scheduler_command' => sprintf('%s %s schedule:run', $binary, base_path('artisan')),
                'scheduler_artisan_path' => base_path('artisan'),
                'updated_at' => $settings->updated_at?->toIso8601String(),
                'updated_by' => $settings->updatedBy?->name,
            ],
            'statusChecks' => $checks,
            'testTarget' => $actor->email,
        ];
    }
}
