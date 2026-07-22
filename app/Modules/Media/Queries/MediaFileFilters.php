<?php

namespace App\Modules\Media\Queries;

use App\Models\MediaFile;
use App\Modules\Media\Support\MediaOptions;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class MediaFileFilters
{
    public function __construct(private readonly TableQuery $tables) {}

    /** @return array<string, mixed> */
    public function fromRequest(Request $request): array
    {
        $filters = $this->tables->filters($request, [
            'visibility' => 'all',
            'collection' => 'all',
        ]);

        if (! in_array($filters['visibility'], ['all', ...MediaOptions::VISIBILITIES], true)) {
            $filters['visibility'] = 'all';
        }

        if (! is_string($filters['collection']) || mb_strlen($filters['collection']) > 80) {
            $filters['collection'] = 'all';
        }

        return $filters;
    }

    /**
     * @param  Builder<MediaFile>  $media
     * @param  array<string, mixed>  $filters
     */
    public function apply(Builder $media, array $filters): void
    {
        $this->applyPortfolio($media, $filters);
        $this->tables->exact($media, $filters, 'visibility');
        $this->tables->exact($media, $filters, 'collection');
        $this->tables->search($media, (string) $filters['search'], [
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
     * @param  Builder<MediaFile>  $media
     * @param  array<string, mixed>  $filters
     */
    public function applyPortfolio(Builder $media, array $filters): void
    {
        $this->tables->exact($media, $filters, 'portfolio_id');
    }
}
