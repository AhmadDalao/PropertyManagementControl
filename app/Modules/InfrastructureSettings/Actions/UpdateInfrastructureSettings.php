<?php

namespace App\Modules\InfrastructureSettings\Actions;

use App\Models\InfrastructureSetting;
use App\Models\User;
use App\Modules\InfrastructureSettings\Support\InfrastructureSettingsAccess;
use Illuminate\Support\Facades\DB;

final readonly class UpdateInfrastructureSettings
{
    public function __construct(private InfrastructureSettingsAccess $access) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, array $data): InfrastructureSetting
    {
        $this->access->ensureSuperadmin($actor);

        return DB::transaction(function () use ($actor, $data): InfrastructureSetting {
            $settings = InfrastructureSetting::query()
                ->lockForUpdate()
                ->firstOrNew(['id' => 1]);
            $settings->fill([
                'mail_enabled' => $data['mail_enabled'],
                'mail_host' => $data['mail_host'],
                'mail_port' => $data['mail_port'],
                'mail_scheme' => $data['mail_scheme'],
                'mail_username' => $data['mail_username'],
                'mail_from_address' => $data['mail_from_address'],
                'mail_from_name' => $data['mail_from_name'],
                'scheduler_php_binary' => $data['scheduler_php_binary'],
                'updated_by_user_id' => $actor->id,
            ]);

            if ($data['clear_mail_password']) {
                $settings->mail_password = null;
            } elseif ($data['mail_password'] !== null) {
                $settings->mail_password = $data['mail_password'];
            }

            $changed = array_values(array_diff(
                array_keys($settings->getDirty()),
                ['mail_password', 'updated_at'],
            ));
            $passwordChanged = $settings->isDirty('mail_password');
            $settings->save();

            activity('system')
                ->causedBy($actor)
                ->performedOn($settings)
                ->event('infrastructure_settings_updated')
                ->withProperties([
                    'changed_fields' => $changed,
                    'mail_password_changed' => $passwordChanged,
                    'mail_enabled' => $settings->mail_enabled,
                ])
                ->log('infrastructure_settings_updated');

            return $settings->refresh();
        });
    }
}
