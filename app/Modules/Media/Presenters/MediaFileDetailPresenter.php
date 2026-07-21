<?php

namespace App\Modules\Media\Presenters;

use App\Models\MediaFile;
use App\Models\User;
use App\Modules\Media\Support\MediaAccess;
use App\Modules\Media\Support\MediaUsage;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Users\Support\UserAccess;

class MediaFileDetailPresenter
{
    public function __construct(
        private readonly MediaAccess $access,
        private readonly MediaUsage $usage,
        private readonly ResourcePresenter $resources,
        private readonly UserAccess $users,
    ) {}

    /** @return array<string, mixed> */
    public function present(MediaFile $mediaFile, User $actor): array
    {
        $this->access->ensureCanManage($actor, $mediaFile);
        $mediaFile->loadMissing(['portfolio', 'uploadedBy']);
        $title = $this->resources->localized($mediaFile->title_en, $mediaFile->title_ar)
            ?: basename($mediaFile->path);
        $alt = $this->resources->localized($mediaFile->alt_text_en, $mediaFile->alt_text_ar) ?: $title;
        $fileUrl = route('media-files.file', $mediaFile);
        $usageCount = $this->usage->cmsSectionCount($mediaFile);

        return [
            'header' => [
                'eyebrow' => trans('app.media.detail_eyebrow'),
                'title' => $title,
                'description' => trans('app.media.detail_description', [
                    'collection' => $mediaFile->collection,
                    'visibility' => trans("app.media.{$mediaFile->visibility}"),
                ]),
                'backHref' => route('media-files.index'),
                'backLabel' => trans('app.media.all_media'),
                'actions' => [
                    ['label' => trans('app.media.edit_media'), 'href' => route('media-files.edit', $mediaFile), 'variant' => 'primary'],
                    ['label' => trans('app.media.open_image'), 'href' => $fileUrl, 'variant' => 'secondary', 'external' => true],
                ],
            ],
            'spotlight' => [
                'eyebrow' => trans('app.media.preview'),
                'title' => $title,
                'description' => $alt,
                'status' => trans("app.media.{$mediaFile->visibility}"),
                'image' => ['src' => $fileUrl, 'alt' => $alt],
                'items' => $this->resources->detailItems([
                    ['label' => trans('app.media.dimensions'), 'value' => $this->dimensions($mediaFile)],
                    ['label' => trans('app.media.size'), 'value' => $this->formatBytes((int) $mediaFile->size)],
                    ['label' => trans('app.media.cms_usage'), 'value' => trans('app.media.section_usage_count', ['count' => $usageCount])],
                ]),
            ],
            'stats' => $this->resources->detailItems([
                ['label' => trans('app.media.collection'), 'value' => $mediaFile->collection, 'tone' => 'primary'],
                ['label' => trans('app.media.visibility'), 'value' => trans("app.media.{$mediaFile->visibility}")],
                ['label' => trans('app.media.size'), 'value' => $this->formatBytes((int) $mediaFile->size)],
                ['label' => trans('app.media.format'), 'value' => $mediaFile->mime_type],
            ]),
            'sections' => [[
                'title' => trans('app.media.record_section'),
                'description' => trans('app.media.record_section_help'),
                'items' => $this->resources->detailItems([
                    ['label' => trans('app.media.title_en'), 'value' => $mediaFile->title_en],
                    ['label' => trans('app.media.title_ar'), 'value' => $mediaFile->title_ar],
                    ['label' => trans('app.media.alt_text_en'), 'value' => $mediaFile->alt_text_en],
                    ['label' => trans('app.media.alt_text_ar'), 'value' => $mediaFile->alt_text_ar],
                    ['label' => trans('app.media.portfolio'), 'value' => $this->resources->localized($mediaFile->portfolio?->name_en, $mediaFile->portfolio?->name_ar), 'href' => $mediaFile->portfolio ? route('portfolios.show', $mediaFile->portfolio) : null],
                    ['label' => trans('app.media.uploaded_by'), 'value' => $mediaFile->uploadedBy?->name, 'href' => $this->users->recordHref($actor, $mediaFile->uploadedBy)],
                    ['label' => trans('app.media.uploaded_at'), 'value' => $mediaFile->created_at?->toDateTimeString()],
                    ['label' => trans('app.media.storage'), 'value' => $mediaFile->disk === 'public' ? trans('app.media.public_storage') : trans('app.media.private_storage')],
                ]),
            ]],
            'related' => [],
            'documents' => [],
            'timeline' => $this->resources->activityTimeline($mediaFile),
        ];
    }

    private function dimensions(MediaFile $mediaFile): string
    {
        return $mediaFile->width && $mediaFile->height
            ? $mediaFile->width.' x '.$mediaFile->height.' px'
            : trans('app.media.unknown_dimensions');
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return number_format($bytes / 1048576, 1).' MB';
    }
}
