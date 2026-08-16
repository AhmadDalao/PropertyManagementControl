<?php

namespace App\Modules\Notifications\Queries;

use App\Models\Document;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Shared\Authorization\AssignedPropertyScope;
use Illuminate\Support\Collection;

final class OperationalNotificationRecipientsQuery
{
    public function __construct(private readonly AssignedPropertyScope $assignments) {}

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

        if ($actor->hasRole('tenant')) {
            $recipientIds->push(...$this->portfolioOperationsIds($record));
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

    /** @return Collection<int, int> */
    private function portfolioOperationsIds(Lease|Payment|Document $record): Collection
    {
        $portfolioId = (int) $record->getAttribute('portfolio_id');

        return User::query()
            ->where('portfolio_id', $portfolioId)
            ->where('status', 'active')
            ->whereHas('roles', fn ($roles) => $roles->whereIn('name', ['owner', 'property_manager']))
            ->get()
            ->filter(fn (User $user): bool => $user->hasRole('owner') || $this->allowsRecord($user, $record))
            ->pluck('id');
    }

    private function allowsRecord(User $manager, Lease|Payment|Document $record): bool
    {
        if ($record instanceof Payment) {
            return $this->assignments->allowsPayment($manager, $record);
        }

        if ($record instanceof Lease) {
            return $this->assignments->allowsLease($manager, $record);
        }

        $record->loadMissing('documentable');
        $attachment = $record->documentable;

        return match (true) {
            $attachment instanceof Payment => $this->assignments->allowsPayment($manager, $attachment),
            $attachment instanceof Lease => $this->assignments->allowsLease($manager, $attachment),
            default => false,
        };
    }
}
