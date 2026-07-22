<?php

namespace App\Modules\Expenses\Data;

use App\Models\ExpenseEntry;
use App\Models\User;

final readonly class ExpenseFormData
{
    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<int, array{value:int,label:string}>  $portfolios
     * @param  array<int, array{value:int,label:string}>  $assets
     * @param  array<int, array{value:int,label:string}>  $maintenanceRequests
     */
    public function __construct(
        public User $actor,
        public ?ExpenseEntry $expense,
        public array $defaults,
        public ?int $portfolioId,
        public string $currency,
        public array $portfolios,
        public array $assets,
        public array $maintenanceRequests,
    ) {}
}
