<?php

namespace App\Modules\Reports\Support;

use App\Models\User;
use App\Modules\Assets\Support\PropertyScope;

final readonly class ReportPropertyScope
{
    public function __construct(
        private PropertyScope $properties,
    ) {}

    /**
     * @return array<int, array{id:int,portfolio_id:int,name:string}>
     */
    public function options(User $actor): array
    {
        return $this->properties->options($actor);
    }

    /**
     * @return array<int, int>|null
     */
    public function assetIds(
        User $actor,
        ?int $portfolioId,
        ?int $propertyId,
    ): ?array {
        return $this->properties->assetIds($actor, $portfolioId, $propertyId);
    }

    public function label(User $actor, ?int $portfolioId, ?int $propertyId): ?string
    {
        return $this->properties->label($actor, $portfolioId, $propertyId);
    }

    /** @return array<int, string> */
    public function leaseableTypes(): array
    {
        return $this->properties->leaseableTypes();
    }
}
