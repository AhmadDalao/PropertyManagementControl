<?php

namespace App\Modules\Reports\Presenters;

use App\Models\Asset;
use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Reports\Queries\PortfolioReportQuery;
use App\Modules\Reports\Support\ReportPropertyScope;
use App\Modules\Shared\PortfolioScope;

final readonly class OwnerStatementPresenter
{
    public function __construct(
        private PortfolioReportQuery $reports,
        private PortfolioScope $portfolios,
        private ReportPropertyScope $properties,
    ) {}

    /**
     * @param  array{date_from:string,date_to:string,portfolio_id:int|null,property_id:int|null}  $filters
     * @return array<string, mixed>
     */
    public function present(User $actor, array $filters, bool $forExport = false): array
    {
        $portfolioId = $filters['portfolio_id'] ?? $actor->portfolio_id;
        $portfolio = $portfolioId ? Portfolio::query()->find($portfolioId) : null;
        $property = $filters['property_id']
            ? Asset::query()->find($filters['property_id'])
            : null;

        return [
            ...$this->reports->handle($actor, $filters, $forExport),
            'filters' => $filters,
            'portfolioOptions' => $this->portfolios->options($actor),
            'propertyOptions' => $this->properties->options($actor),
            'statement' => [
                'portfolio' => $this->bilingualName($portfolio?->name_en, $portfolio?->name_ar, 'all_portfolios'),
                'property' => $this->bilingualName($property?->title_en, $property?->title_ar, 'all_properties'),
                'prepared_for' => $actor->name,
                'generated_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * @return array{en:string,ar:string}
     */
    private function bilingualName(?string $english, ?string $arabic, string $fallbackKey): array
    {
        return [
            'en' => trim((string) $english) ?: trans("app.reports.{$fallbackKey}", locale: 'en'),
            'ar' => trim((string) $arabic) ?: trans("app.reports.{$fallbackKey}", locale: 'ar'),
        ];
    }
}
