<?php

namespace App\Modules\Notifications\Actions;

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Modules\Notifications\Notifications\MaintenanceActivityNotification;
use App\Modules\Notifications\Queries\MaintenanceNotificationRecipientsQuery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

final readonly class SendMaintenanceActivityNotification
{
    public function __construct(
        private MaintenanceNotificationRecipientsQuery $recipients,
    ) {}

    public function created(User $actor, MaintenanceRequest $request): void
    {
        if ($actor->hasRole('tenant')) {
            $this->management($actor, $request, 'maintenance_created');

            return;
        }

        $this->tenant(
            $actor,
            $request,
            $request->status === 'resolved'
                ? 'maintenance_resolved'
                : 'maintenance_created_for_tenant',
        );
    }

    public function updated(
        User $actor,
        MaintenanceRequest $request,
        string $previousStatus,
        bool $public,
    ): void {
        if ($actor->hasRole('tenant')) {
            $this->management($actor, $request, 'maintenance_tenant_comment');

            return;
        }

        if ($request->status === 'resolved' && $previousStatus !== 'resolved') {
            $this->tenant($actor, $request, 'maintenance_resolved');
        } elseif ($public || $request->status !== $previousStatus) {
            $this->tenant($actor, $request, 'maintenance_updated');
        }
    }

    public function cancelled(User $actor, MaintenanceRequest $request): void
    {
        $actor->hasRole('tenant')
            ? $this->management($actor, $request, 'maintenance_cancelled')
            : $this->tenant($actor, $request, 'maintenance_cancelled');
    }

    public function resolutionResponse(
        User $actor,
        MaintenanceRequest $request,
        string $outcome,
    ): void {
        $this->management(
            $actor,
            $request,
            $outcome === 'confirmed'
                ? 'maintenance_confirmed'
                : 'maintenance_reopened',
        );
    }

    private function management(User $actor, MaintenanceRequest $request, string $event): void
    {
        $this->send($this->recipients->management($request, $actor), $actor, $request, $event);
    }

    private function tenant(User $actor, MaintenanceRequest $request, string $event): void
    {
        $this->send($this->recipients->tenant($request, $actor), $actor, $request, $event);
    }

    /** @param Collection<int, User> $recipients */
    private function send(
        Collection $recipients,
        User $actor,
        MaintenanceRequest $request,
        string $event,
    ): void {
        if ($recipients->isNotEmpty()) {
            Notification::send(
                $recipients,
                new MaintenanceActivityNotification($event, $request, $actor),
            );
        }
    }
}
