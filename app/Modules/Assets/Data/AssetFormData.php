<?php

namespace App\Modules\Assets\Data;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Collection;

final readonly class AssetFormData
{
    /**
     * @param  array<int, array{id:int,name:string}>  $portfolios
     * @param  Collection<int, Asset>  $parents
     * @param  Collection<int, User>  $owners
     * @param  Collection<int, User>  $managers
     */
    public function __construct(
        public int $portfolioId,
        public array $portfolios,
        public Collection $parents,
        public Collection $owners,
        public Collection $managers,
        public ?int $ownerId,
        public ?int $managerId,
    ) {}
}
