<?php

namespace App\Modules\CompanyControl\Support;

final class CompanyControlOptions
{
    public const DATA_SOURCES = ['live', 'showcase', 'all'];

    public const STATUSES = ['active', 'inactive', 'archived', 'all'];

    public const ATTENTION = ['all', 'risk', 'watch', 'on_track'];

    public const SORTS = [
        'attention',
        'valuation',
        'arrears',
        'occupancy',
        'collection',
        'net',
        'name',
    ];

    public const DIRECTIONS = ['asc', 'desc'];

    public const PAGE_SIZES = [12, 24, 48];
}
