<?php

namespace App\Modules\PortfolioControl\Support;

final class PortfolioControlOptions
{
    public const ATTENTION = ['all', 'risk', 'watch', 'on_track'];

    public const SORTS = [
        'attention',
        'arrears',
        'occupancy',
        'collection',
        'net',
        'name',
    ];

    public const PAGE_SIZES = [12, 24, 48];
}
