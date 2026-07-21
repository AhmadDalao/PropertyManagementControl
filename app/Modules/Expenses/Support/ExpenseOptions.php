<?php

namespace App\Modules\Expenses\Support;

final class ExpenseOptions
{
    /** @var array<int, string> */
    public const CATEGORIES = [
        'maintenance',
        'utilities',
        'supplies',
        'repairs',
        'insurance',
        'taxes',
        'administration',
    ];

    /** @var array<int, string> */
    public const STATUSES = ['posted', 'pending', 'void'];

    /** @var array<int, string> */
    public const MUTABLE_STATUSES = ['posted', 'pending'];
}
