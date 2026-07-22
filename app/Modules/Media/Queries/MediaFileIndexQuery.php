<?php

namespace App\Modules\Media\Queries;

use App\Models\MediaFile;
use App\Models\User;
use App\Modules\Media\Presenters\MediaFileTableRowPresenter;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class MediaFileIndexQuery
{
    public function __construct(
        private readonly MediaFileDirectoryQuery $directory,
        private readonly MediaFileFilters $filters,
        private readonly MediaFileInsightsQuery $insights,
        private readonly MediaFileTableRowPresenter $rows,
        private readonly PortfolioScope $portfolios,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Request $request, User $actor): array
    {
        $filters = $this->filters->fromRequest($request);
        $baseQuery = $this->directory->base($actor);
        $mediaFiles = $this->directory->listing(clone $baseQuery);
        $this->filters->apply($mediaFiles, $filters);
        $metricScope = clone $baseQuery;
        $this->filters->applyPortfolio($metricScope, $filters);

        return [
            'mediaFiles' => $this->directory
                ->paginate($mediaFiles, $filters)
                ->through(fn (MediaFile $mediaFile) => $this->rows->present($mediaFile)),
            'mediaInsights' => $this->insights->metrics($metricScope),
            'filters' => $filters,
            'counts' => $this->insights->counts($metricScope, $filters),
            'portfolioOptions' => $this->portfolios->options($actor),
            'collectionOptions' => (clone $metricScope)
                ->whereNotNull('collection')
                ->distinct()
                ->orderBy('collection')
                ->pluck('collection')
                ->values(),
        ];
    }

    /** @return Builder<MediaFile> */
    public function forExport(Request $request, User $actor): Builder
    {
        $filters = $this->filters->fromRequest($request);
        $media = $this->directory->listing($this->directory->base($actor));
        $this->filters->apply($media, $filters);

        return $media;
    }
}
