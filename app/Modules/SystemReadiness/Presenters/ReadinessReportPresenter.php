<?php

namespace App\Modules\SystemReadiness\Presenters;

use App\Models\User;

final readonly class ReadinessReportPresenter
{
    public function __construct(private ReadinessPagePresenter $page) {}

    /** @return array<string, mixed> */
    public function present(User $actor, ?int $portfolioId): array
    {
        $data = $this->page->present($actor, $portfolioId);

        unset($data['mailTest'], $data['portfolioOptions']);

        return [
            ...$data,
            'preparedBy' => $actor->name,
            'generatedAt' => now()->translatedFormat('Y-m-d H:i T'),
        ];
    }
}
