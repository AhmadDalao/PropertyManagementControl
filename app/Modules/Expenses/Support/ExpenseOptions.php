<?php

namespace App\Modules\Expenses\Support;

use Illuminate\Support\Str;

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
        'cleaning',
        'security',
        'management',
        'compliance',
    ];

    /** @var array<int, string> */
    public const SHOWCASE_CATEGORIES = [
        'maintenance',
        'utilities',
        'cleaning',
        'security',
        'insurance',
        'management',
    ];

    /** @var array<int, string> */
    public const STATUSES = ['posted', 'pending', 'void'];

    /** @var array<int, string> */
    public const MUTABLE_STATUSES = ['posted', 'pending'];

    public static function label(string $category): string
    {
        $key = "app.expenses.category_{$category}";
        $translated = trans($key);

        return $translated === $key ? Str::headline($category) : $translated;
    }
}
