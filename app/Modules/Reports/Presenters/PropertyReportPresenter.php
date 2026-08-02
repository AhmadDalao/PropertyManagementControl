<?php

namespace App\Modules\Reports\Presenters;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Reports\Queries\PortfolioReportQuery;
use App\Modules\Reports\Queries\PropertyReportContextQuery;

final readonly class PropertyReportPresenter
{
    public function __construct(
        private PortfolioReportQuery $reports,
        private PropertyReportContextQuery $context,
    ) {}

    /**
     * @param  array{date_from:string,date_to:string,portfolio_id:int,property_id:int}  $filters
     * @return array<string, mixed>
     */
    public function present(User $actor, Asset $property, array $filters): array
    {
        $context = $this->context->handle($actor, $property);
        $query = [
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
            'portfolio_id' => $property->portfolio_id,
            'property_id' => $property->id,
        ];

        return [
            ...$this->reports->handle($actor, $filters),
            'filters' => $filters,
            'property' => [
                ...$context,
                'downloads' => [
                    'xlsx' => route('reports.statement.workbook', $query, false),
                    'pdf' => route('reports.statement.pdf', $query, false),
                    'docx' => route('reports.statement.word', $query, false),
                ],
            ],
        ];
    }
}
