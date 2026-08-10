<?php

namespace App\Modules\InfrastructureSettings\Actions;

use App\Models\InfrastructureSetting;
use App\Modules\InfrastructureSettings\Support\InfrastructureSettingsState;
use Illuminate\Contracts\Config\Repository;
use Throwable;

final class ApplyInfrastructureSettings
{
    /** @var array<string, mixed> */
    private array $fallback;

    public function __construct(
        private readonly Repository $config,
        private readonly InfrastructureSettingsState $state,
    ) {
        $this->fallback = [
            'mail.default' => $config->get('mail.default'),
            'mail.mailers.smtp.scheme' => $config->get('mail.mailers.smtp.scheme'),
            'mail.mailers.smtp.host' => $config->get('mail.mailers.smtp.host'),
            'mail.mailers.smtp.port' => $config->get('mail.mailers.smtp.port'),
            'mail.mailers.smtp.username' => $config->get('mail.mailers.smtp.username'),
            'mail.mailers.smtp.password' => $config->get('mail.mailers.smtp.password'),
            'mail.from.address' => $config->get('mail.from.address'),
            'mail.from.name' => $config->get('mail.from.name'),
            'operations.scheduler_php_binary' => $config->get('operations.scheduler_php_binary'),
        ];
    }

    public function handle(): void
    {
        $this->config->set($this->fallback);

        try {
            $settings = InfrastructureSetting::query()->first();

            if (! $settings) {
                $this->purgeMailer();

                return;
            }

            if ($settings->scheduler_php_binary) {
                $this->config->set(
                    'operations.scheduler_php_binary',
                    $settings->scheduler_php_binary,
                );
            }

            if ($this->state->smtpReady($settings)) {
                $this->config->set([
                    'mail.default' => 'smtp',
                    'mail.mailers.smtp.scheme' => $settings->mail_scheme,
                    'mail.mailers.smtp.host' => $settings->mail_host,
                    'mail.mailers.smtp.port' => $settings->mail_port,
                    'mail.mailers.smtp.username' => $settings->mail_username,
                    'mail.mailers.smtp.password' => $settings->mail_password,
                    'mail.from.address' => $settings->mail_from_address,
                    'mail.from.name' => $settings->mail_from_name,
                ]);
            }
        } catch (Throwable) {
            $this->config->set($this->fallback);
        }

        $this->purgeMailer();
    }

    private function purgeMailer(): void
    {
        if (app()->resolved('mail.manager')) {
            app('mail.manager')->purge();
        }
    }
}
