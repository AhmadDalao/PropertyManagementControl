<?php

namespace App\Modules\Reports\Presenters;

use App\Models\MaintenanceRequest;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Support\Collection;

final readonly class ReportMaintenanceRowsPresenter
{
    public function __construct(private PortfolioScope $portfolios) {}

    /**
     * @param  Collection<int, MaintenanceRequest>  $backlog
     * @return array<int, array<string, mixed>>
     */
    public function present(Collection $backlog): array
    {
        return $backlog
            ->take(8)
            ->map(fn (MaintenanceRequest $request): array => [
                'id' => $request->id,
                'title' => $request->title,
                'asset' => $this->portfolios->localized(
                    $request->asset?->title_en,
                    $request->asset?->title_ar,
                ),
                'tenant' => $request->tenantProfile?->user?->name,
                'status' => $request->status,
                'priority' => $request->priority,
                'created_at' => $request->created_at?->toDateString(),
            ])
            ->values()
            ->all();
    }
}
