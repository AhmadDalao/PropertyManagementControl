<?php

namespace App\Modules\Assets\Support;

use App\Models\User;
use Illuminate\Validation\ValidationException;

final class BuildingStructureReferenceGuard
{
    public function __construct(private readonly AssetReferenceGuard $references) {}

    /** @param array<string, mixed> $data */
    public function ensure(array $data, int $portfolioId): void
    {
        $this->references->ensure([
            'primary_owner_user_id' => $data['primary_owner_user_id'] ?? null,
            'primary_manager_user_id' => $data['primary_manager_user_id'] ?? null,
        ], $portfolioId);

        foreach ([
            'primary_owner_user_id' => trans('app.errors.owner_assignment_invalid'),
            'primary_manager_user_id' => trans('app.errors.manager_assignment_invalid'),
        ] as $field => $message) {
            $active = User::query()
                ->whereKey((int) $data[$field])
                ->where('portfolio_id', $portfolioId)
                ->where('status', 'active')
                ->exists();

            if (! $active) {
                throw ValidationException::withMessages([$field => $message]);
            }
        }
    }
}
