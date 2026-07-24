<?php

namespace App\Modules\RentCollection\Support;

final class RentCollectionOptions
{
    /** @var array<int, string> */
    public const STATUSES = ['actionable', 'open', 'overdue', 'partial', 'paid'];

    /** @var array<int, string> */
    public const LINE_TYPES = ['rent', 'deposit'];
}
