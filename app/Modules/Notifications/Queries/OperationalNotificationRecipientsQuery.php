<?php

namespace App\Modules\Notifications\Queries;

use App\Models\Document;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Support\Collection;

final class OperationalNotificationRecipientsQuery
{
    /** @return Collection<int, User> */
    public function affected(Lease|Payment|Document $record, User $actor): Collection
    {
        $recipientIds = collect();
        $tenant = $this->tenant($record);

        if ($tenant?->status === 'active') {
            $recipientIds->push($tenant->id);
        }

        if ($actor->hasRole('property_manager')) {
            $recipientIds->push(...$this->portfolioOwnerIds(
                (int) $record->getAttribute('portfolio_id'),
            ));
        }

        return User::query()
            ->whereIn('id', $recipientIds->unique()->filter())
            ->where('status', 'active')
            ->where('id', '!=', $actor->id)
            ->get();
    }

    private function tenant(Lease|Payment|Document $record): ?User
    {
        if ($record instanceof Document) {
            $record->loadMissing('documentable');
            $attachment = $record->documentable;

            return $attachment instanceof Lease || $attachment instanceof Payment
                ? $this->tenant($attachment)
                : null;
        }

        $record->loadMissing('tenantProfile.user');

        return $record->tenantProfile?->user;
    }

    /** @return Collection<int, int> */
    private function portfolioOwnerIds(int $portfolioId): Collection
    {
        $primaryOwnerId = Portfolio::query()->whereKey($portfolioId)->value('owner_user_id');

        return User::query()
            ->where('status', 'active')
            ->where(function ($users) use ($portfolioId, $primaryOwnerId): void {
                $users->where(function ($owners) use ($portfolioId): void {
                    $owners->where('portfolio_id', $portfolioId)
                        ->whereHas('roles', fn ($roles) => $roles->where('name', 'owner'));
                });

                if ($primaryOwnerId) {
                    $users->orWhere('id', $primaryOwnerId);
                }
            })
            ->pluck('id');
    }
}
