<?php

namespace App\Modules\Maintenance\Actions;

use App\Models\MaintenanceAttachment;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

final class AddMaintenanceAttachments
{
    public function __construct(private readonly PersistMaintenanceAttachments $attachments) {}

    /**
     * @param  array<int, mixed>  $files
     * @return Collection<int, MaintenanceAttachment>
     */
    public function handle(User $actor, MaintenanceRequest $request, array $files): Collection
    {
        $persisted = collect();

        try {
            DB::transaction(function () use ($actor, $request, $files, &$persisted): void {
                $persisted = $this->attachments->handle($actor, $request, $files);
            });
        } catch (Throwable $exception) {
            $this->attachments->discard($persisted);

            throw $exception;
        }

        return $persisted;
    }
}
