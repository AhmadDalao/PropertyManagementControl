<?php

namespace App\Modules\Media\Queries;

use App\Models\MediaFile;
use App\Models\User;
use App\Modules\Media\Support\MediaAccess;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class MediaFileDirectoryQuery
{
    public function __construct(
        private readonly MediaAccess $access,
        private readonly TableQuery $tables,
    ) {}

    /** @return Builder<MediaFile> */
    public function base(User $actor): Builder
    {
        return $this->access->directoryScope(MediaFile::query(), $actor);
    }

    /**
     * @param  Builder<MediaFile>  $query
     * @return Builder<MediaFile>
     */
    public function listing(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'portfolio_id',
                'uploaded_by_user_id',
                'collection',
                'path',
                'mime_type',
                'size',
                'width',
                'height',
                'title_en',
                'title_ar',
                'alt_text_en',
                'alt_text_ar',
                'visibility',
                'created_at',
            ])
            ->with(['portfolio:id,name_en,name_ar', 'uploadedBy:id,name']);
    }

    /**
     * @param  Builder<MediaFile>  $query
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, MediaFile>
     */
    public function paginate(Builder $query, array $filters): LengthAwarePaginator
    {
        return $this->tables->paginate($query, $filters, [
            'created_at',
            'title_en',
            'collection',
            'visibility',
            'size',
            'width',
            'height',
        ]);
    }
}
