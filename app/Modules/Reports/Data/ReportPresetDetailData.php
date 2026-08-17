<?php

namespace App\Modules\Reports\Data;

use App\Models\ReportPreset;
use App\Models\User;

final readonly class ReportPresetDetailData
{
    /** @param array<string, mixed> $view */
    public function __construct(
        public ReportPreset $preset,
        public User $actor,
        public array $view,
        public string $title,
        public string $period,
        public string $dateRange,
        public string $visibility,
        public string $portfolioScope,
        public string $createdAt,
        public string $updatedAt,
    ) {}
}
