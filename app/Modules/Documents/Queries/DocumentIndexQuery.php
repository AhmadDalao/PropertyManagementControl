<?php

namespace App\Modules\Documents\Queries;

use App\Models\Document;
use App\Models\User;
use App\Modules\Documents\Presenters\DocumentTableRowPresenter;
use App\Modules\Documents\Support\DocumentOptions;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class DocumentIndexQuery
{
    public function __construct(
        private readonly DocumentDirectoryQuery $directory,
        private readonly DocumentFilters $filters,
        private readonly DocumentInsightsQuery $insights,
        private readonly DocumentTableRowPresenter $rows,
        private readonly PortfolioScope $portfolios,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Request $request, User $actor): array
    {
        $filters = $this->filters->fromRequest($request);
        $baseQuery = $this->directory->base($actor);
        $documents = $this->directory->listing(clone $baseQuery);
        $this->filters->apply($documents, $filters);
        $metricScope = clone $baseQuery;
        $this->filters->applyPortfolio($metricScope, $filters);

        return [
            'documents' => $this->directory
                ->paginate($documents, $filters)
                ->through(fn (Document $document) => $this->rows->present($document)),
            'documentInsights' => $this->insights->metrics($metricScope),
            'filters' => $filters,
            'counts' => $this->insights->counts($metricScope, $filters),
            'portfolioOptions' => $this->portfolios->options($actor),
            'typeOptions' => DocumentOptions::TYPES,
            'attachmentOptions' => DocumentOptions::ATTACHMENTS,
            'visibilityOptions' => DocumentOptions::VISIBILITIES,
        ];
    }

    /** @return Builder<Document> */
    public function forExport(Request $request, User $actor): Builder
    {
        $filters = $this->filters->fromRequest($request);
        $documents = $this->directory->listing($this->directory->base($actor));
        $this->filters->apply($documents, $filters);

        return $documents;
    }
}
