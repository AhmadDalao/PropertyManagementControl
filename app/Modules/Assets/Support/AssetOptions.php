<?php

namespace App\Modules\Assets\Support;

final class AssetOptions
{
    /** @var array<int, string> */
    public const TYPES = ['property', 'building', 'floor', 'unit', 'space'];

    /** @var array<int, string> */
    public const USAGES = ['residential', 'commercial', 'mixed', 'personal'];

    /** @var array<int, string> */
    public const MUTABLE_STATUSES = ['active', 'inactive'];

    /** @var array<int, string> */
    public const STATUSES = ['active', 'inactive', 'archived'];

    /** @var array<int, string> */
    public const OCCUPANCIES = ['vacant', 'occupied', 'partially_occupied', 'reserved', 'maintenance'];

    private function __construct() {}
}
