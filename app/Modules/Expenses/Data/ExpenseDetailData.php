<?php

namespace App\Modules\Expenses\Data;

use App\Models\ExpenseEntry;
use App\Models\User;

final readonly class ExpenseDetailData
{
    public function __construct(
        public ExpenseEntry $expense,
        public User $actor,
        public string $category,
        public string $status,
        public string $amount,
    ) {}
}
