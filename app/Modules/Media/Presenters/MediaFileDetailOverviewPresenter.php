<?php

namespace App\Modules\Media\Presenters;

use App\Modules\Media\Data\MediaFileDetailData;
use App\Modules\Media\Support\MediaOptions;
use App\Modules\Shared\ResourcePresenter;
use App\Modules\Users\Support\UserAccess;

final class MediaFileDetailOverviewPresenter
{
    public function __construct(
        private readonly ResourcePresenter $resources,
        private readonly UserAccess $users,
    ) {}

    /** @return array<string, mixed> */
    public function present(MediaFileDetailData $data): array
    {
        $mediaFile = $data->mediaFile;
        $uploader = $mediaFile->uploaded_by_user_id === null ? null : $mediaFile->uploadedBy;

        return [
            'spotlight' => [
                'eyebrow' => trans('app.media.preview'),
                'title' => $data->title,
                'description' => $data->alt,
                'status' => MediaOptions::label($mediaFile->visibility),
                'image' => ['src' => $data->fileUrl, 'alt' => $data->alt],
                'items' => $this->resources->detailItems([
                    ['label' => trans('app.media.dimensions'), 'value' => $this->dimensions($mediaFile->width, $mediaFile->height)],
                    ['label' => trans('app.media.size'), 'value' => $this->formatBytes((int) $mediaFile->size)],
                    ['label' => trans('app.media.cms_usage'), 'value' => trans('app.media.section_usage_count', ['count' => $data->usageCount])],
                ]),
            ],
            'stats' => $this->resources->detailItems([
                ['label' => trans('app.media.collection'), 'value' => $mediaFile->collection, 'tone' => 'primary'],
                ['label' => trans('app.media.visibility'), 'value' => MediaOptions::label($mediaFile->visibility)],
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
                    ['label' => trans('app.media.portfolio'), 'value' => $this->portfolioName($data), 'href' => $mediaFile->portfolio ? route('portfolios.show', $mediaFile->portfolio) : null],
                    ['label' => trans('app.media.uploaded_by'), 'value' => $uploader === null ? trans('app.resource.system') : $uploader->name, 'href' => $this->users->recordHref($data->actor, $uploader)],
                    ['label' => trans('app.media.uploaded_at'), 'value' => $mediaFile->created_at?->toDateTimeString()],
                    ['label' => trans('app.media.storage'), 'value' => $mediaFile->disk === 'public' ? trans('app.media.public_storage') : trans('app.media.private_storage')],
                ]),
            ]],
        ];
    }

    private function portfolioName(MediaFileDetailData $data): string
    {
        $portfolio = $data->mediaFile->portfolio;

        return $portfolio
            ? ($this->resources->localized($portfolio->name_en, $portfolio->name_ar) ?? "#{$portfolio->id}")
            : trans('app.media.global_website');
    }

    private function dimensions(?int $width, ?int $height): string
    {
        return $width && $height
            ? "{$width} x {$height} px"
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
