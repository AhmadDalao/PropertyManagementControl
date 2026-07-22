<?php

namespace App\Modules\Media\Queries;

use App\Models\MediaFile;
use Illuminate\Database\Eloquent\Builder;

final class MediaFileInsightsQuery
{
    /**
     * @param  Builder<MediaFile>  $baseQuery
     * @return array{total:int,public:int,private:int,bytes:int,collections:int}
     */
    public function metrics(Builder $baseQuery): array
    {
        $summary = (clone $baseQuery)
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
            'collections' => (clone $baseQuery)->distinct()->count('collection'),
        ];
    }

    /**
     * @param  Builder<MediaFile>  $baseQuery
     * @param  array<string, mixed>  $filters
     * @return array<int, array{label:string,value:int,filter:array<string, string>,active:bool}>
     */
    public function counts(Builder $baseQuery, array $filters): array
    {
        $visibility = (string) $filters['visibility'];

        return [
            $this->count($baseQuery, trans('app.media.all'), 'all', $visibility === 'all'),
            $this->count($baseQuery, trans('app.media.public'), 'public', $visibility === 'public'),
            $this->count($baseQuery, trans('app.media.private'), 'private', $visibility === 'private'),
        ];
    }

    /**
     * @param  Builder<MediaFile>  $query
     * @return array{label:string,value:int,filter:array<string, string>,active:bool}
     */
    private function count(Builder $query, string $label, string $visibility, bool $active): array
    {
        if ($visibility !== 'all') {
            $query = (clone $query)->where('visibility', $visibility);
        }

        return [
            'label' => $label,
            'value' => (clone $query)->count(),
            'filter' => ['visibility' => $visibility],
            'active' => $active,
        ];
    }
}
