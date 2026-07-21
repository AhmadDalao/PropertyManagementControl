<?php

namespace App\Modules\Media\Queries;

use App\Models\MediaFile;
use App\Models\User;
use App\Modules\Media\Support\MediaAccess;
use App\Modules\Media\Support\MediaOptions;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class MediaFileIndexQuery
{
    public function __construct(
        private readonly MediaAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly TableQuery $tables,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Request $request, User $actor): array
    {
        $filters = $this->filters($request);
        $baseQuery = $this->access->directoryScope(MediaFile::query(), $actor);
        $mediaFiles = (clone $baseQuery)->with(['portfolio:id,name_en,name_ar', 'uploadedBy:id,name']);
        $this->applyFilters($mediaFiles, $filters);
        $metricScope = clone $baseQuery;
        $this->tables->exact($metricScope, $filters, 'portfolio_id');

        return [
            'mediaFiles' => $this->paginate($mediaFiles, $filters),
            'mediaInsights' => $this->insights(clone $metricScope),
            'filters' => $filters,
            'counts' => $this->counts(clone $metricScope, $filters),
            'portfolioOptions' => $this->portfolios->options($actor),
            'collectionOptions' => (clone $metricScope)
                ->whereNotNull('collection')
                ->distinct()
                ->orderBy('collection')
                ->pluck('collection')
                ->values(),
        ];
    }

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        $filters = $this->tables->filters($request, [
            'visibility' => 'all',
            'collection' => 'all',
        ]);

        if (! in_array($filters['visibility'], ['all', ...MediaOptions::VISIBILITIES], true)) {
            $filters['visibility'] = 'all';
        }

        return $filters;
    }

    /**
     * @param  Builder<MediaFile>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $this->tables->exact($query, $filters, 'portfolio_id');
        $this->tables->exact($query, $filters, 'visibility');
        $this->tables->exact($query, $filters, 'collection');
        $this->tables->search($query, (string) $filters['search'], [
            'title_en',
            'title_ar',
            'alt_text_en',
            'alt_text_ar',
            'collection',
            'path',
            'mime_type',
        ]);
    }

    /**
     * @param  Builder<MediaFile>  $query
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginate(Builder $query, array $filters): LengthAwarePaginator
    {
        return $this->tables->paginate($query, $filters, [
            'created_at',
            'title_en',
            'collection',
            'visibility',
            'size',
            'width',
            'height',
        ])->through(fn (MediaFile $mediaFile): array => $this->tableRow($mediaFile));
    }

    /** @return array<string, mixed> */
    private function tableRow(MediaFile $mediaFile): array
    {
        return [
            'id' => $mediaFile->id,
            'title_en' => $mediaFile->title_en,
            'title_ar' => $mediaFile->title_ar,
            'alt_text_en' => $mediaFile->alt_text_en,
            'alt_text_ar' => $mediaFile->alt_text_ar,
            'filename' => basename($mediaFile->path),
            'collection' => $mediaFile->collection,
            'visibility' => $mediaFile->visibility,
            'mime_type' => $mediaFile->mime_type,
            'size' => $mediaFile->size,
            'width' => $mediaFile->width,
            'height' => $mediaFile->height,
            'file_url' => route('media-files.file', $mediaFile),
            'created_at' => $mediaFile->created_at?->toDateTimeString(),
            'portfolio' => [
                'name_en' => $mediaFile->portfolio?->name_en,
                'name_ar' => $mediaFile->portfolio?->name_ar,
            ],
            'uploaded_by' => ['name' => $mediaFile->uploadedBy?->name],
        ];
    }

    /**
     * @param  Builder<MediaFile>  $query
     * @return array<string, int>
     */
    private function insights(Builder $query): array
    {
        $summary = $query
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN visibility = 'public' THEN 1 ELSE 0 END) as public_count")
            ->selectRaw("SUM(CASE WHEN visibility = 'private' THEN 1 ELSE 0 END) as private_count")
            ->selectRaw('COALESCE(SUM(size), 0) as bytes_total')
            ->first();

        return [
            'total' => (int) ($summary?->getAttribute('total') ?? 0),
            'public' => (int) ($summary?->getAttribute('public_count') ?? 0),
            'private' => (int) ($summary?->getAttribute('private_count') ?? 0),
            'bytes' => (int) ($summary?->getAttribute('bytes_total') ?? 0),
            'collections' => (clone $query)->distinct()->count('collection'),
        ];
    }

    /**
     * @param  Builder<MediaFile>  $query
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function counts(Builder $query, array $filters): array
    {
        $visibility = (string) ($filters['visibility'] ?? 'all');

        return [
            ['label' => trans('app.media.all'), 'value' => (clone $query)->count(), 'filter' => ['visibility' => 'all'], 'active' => $visibility === 'all'],
            ['label' => trans('app.media.public'), 'value' => (clone $query)->where('visibility', 'public')->count(), 'filter' => ['visibility' => 'public'], 'active' => $visibility === 'public'],
            ['label' => trans('app.media.private'), 'value' => (clone $query)->where('visibility', 'private')->count(), 'filter' => ['visibility' => 'private'], 'active' => $visibility === 'private'],
        ];
    }
}
