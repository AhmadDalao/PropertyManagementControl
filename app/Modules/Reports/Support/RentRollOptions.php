<?php

namespace App\Modules\Reports\Support;

final class RentRollOptions
{
    /** @var list<string> */
    public const STATES = ['occupied', 'vacant', 'arrears', 'expiring'];

    /** @var array<string, mixed> */
    public const DEFAULT_FILTERS = [
        'search' => '',
        'state' => 'all',
        'portfolio_id' => null,
        'property_id' => null,
        'per_page' => 10,
        'page' => 1,
        'sort' => 'title_en',
        'direction' => 'asc',
    ];
}
