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
    public function present(
        User $actor,
        Asset $property,
        array $filters,
        bool $forExport = false,
    ): array {
        $context = $this->context->handle($actor, $property);

        return [
            ...$this->reports->handle($actor, $filters, $forExport),
            'filters' => $filters,
            'property' => [
                ...$context,
                'downloads' => [
                    'xlsx' => route('reports.properties.workbook', [
                        'asset' => $property->id,
                        'date_from' => $filters['date_from'],
                        'date_to' => $filters['date_to'],
                    ], false),
                    'pdf' => route('reports.properties.pdf', [
                        'asset' => $property->id,
                        'date_from' => $filters['date_from'],
                        'date_to' => $filters['date_to'],
                    ], false),
                    'docx' => route('reports.properties.word', [
                        'asset' => $property->id,
                        'date_from' => $filters['date_from'],
                        'date_to' => $filters['date_to'],
                    ], false),
                ],
            ],
        ];
    }
}
