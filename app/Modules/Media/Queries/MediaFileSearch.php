<?php

namespace App\Modules\Media\Queries;

use App\Models\MediaFile;
use App\Models\User;
use App\Modules\Media\Support\MediaAccess;
use App\Modules\Search\Presenters\SearchResultPresenter;
use App\Modules\Search\Support\ModuleSearchSource;
use App\Modules\Shared\TableQuery;

class MediaFileSearch extends ModuleSearchSource
{
    public function __construct(
        private readonly MediaAccess $access,
        private readonly TableQuery $tables,
        private readonly SearchResultPresenter $results,
    ) {}

    public function results(User $actor, string $query): array
    {
        if (! $this->isManager($actor) || ! $this->moduleEnabled($actor, 'media')) {
            return [];
        }

        $media = $this->access->directoryScope(MediaFile::query(), $actor);
        $this->tables->search($media, $query, [
            'title_en',
            'title_ar',
            'alt_text_en',
            'alt_text_ar',
            'collection',
            'path',
        ]);

        return $media
            ->limit(4)
            ->get()
            ->map(fn (MediaFile $file): array => $this->results->result(
                trans('app.nav.media'),
                $this->results->localized($file->title_en, $file->title_ar)
                    ?? trans('app.nav.media').' #'.$file->id,
                $file->collection,
                $this->results->status($file->visibility),
                route('media-files.show', $file),
            ))
            ->all();
    }
}
