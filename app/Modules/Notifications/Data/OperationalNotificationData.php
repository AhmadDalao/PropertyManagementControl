<?php

namespace App\Modules\Notifications\Data;

final readonly class OperationalNotificationData
{
    /**
     * @param  array<string, scalar|null>  $replacementsEn
     * @param  array<string, scalar|null>  $replacementsAr
     */
    public function __construct(
        public string $event,
        public array $replacementsEn,
        public array $replacementsAr,
        public string $url,
        public string $icon,
        public string $tone,
        public string $resourceType,
        public int $resourceId,
        public int $portfolioId,
        public int $actorUserId,
    ) {}
}
