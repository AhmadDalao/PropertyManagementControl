<?php

namespace App\Modules\Payments\Data;

use App\Models\User;

final readonly class PaymentFormData
{
    /**
     * @param  array<string, mixed>  $defaults
     * @param  array<int, array{value:int,label:string}>  $portfolios
     * @param  array<int, array{value:int,label:string}>  $leases
     */
    public function __construct(
        public User $actor,
        public array $defaults,
        public ?int $portfolioId,
        public array $portfolios,
        public array $leases,
    ) {}
}
