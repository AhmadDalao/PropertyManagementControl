<?php

namespace App\Modules\Documentation\Support;

use App\Models\User;
use Illuminate\Support\Collection;

class DocumentationCatalog
{
    public function __construct(
        private readonly DocumentationConfiguration $configuration,
        private readonly DocumentationAccess $access,
        private readonly DocumentationLocalizer $localizer,
        private readonly DocumentationScope $scope,
    ) {}

    /** @return Collection<int, array<string, mixed>> */
    public function items(User $actor, string $collection, bool $scope = true): Collection
    {
        if (! $this->configuration->supports($collection)) {
            return collect();
        }

        return collect($this->configuration->items($collection))
            ->when(
                $scope,
                fn (Collection $items) => $items->filter(
                    fn (array $item): bool => $this->access->canSee($actor, $item),
                ),
            )
            ->map(fn (array $item): array => $this->localizer->localize($item, $collection))
            ->map(fn (array $item): array => $this->scope->apply($actor, $collection, $item))
            ->values();
    }
}
