<?php

namespace App\Modules\Reports\Presenters;

use App\Models\User;

final readonly class ArrearsAgingScopePresenter
{
    public function __construct(
        private ReportLibraryScopePresenter $scopes,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, array{id:int,name:string}>  $portfolios
     * @param  array<int, array{id:int,portfolio_id:int,name:string}>  $properties
     * @return array<int, array{label:string,value:string}>
     */
    public function present(
        User $actor,
        array $filters,
        array $portfolios,
        array $properties,
    ): array {
        return $this->scopes->present(
            $actor,
            [
                'period' => 'custom',
                'date_from' => today()->toDateString(),
                'date_to' => today()->toDateString(),
                'portfolio_id' => $filters['portfolio_id'],
                'property_id' => $filters['property_id'],
            ],
            $portfolios,
            $properties,
        )['current'];
    }
}
