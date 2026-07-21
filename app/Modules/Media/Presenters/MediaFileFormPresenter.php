<?php

namespace App\Modules\Media\Presenters;

use App\Models\MediaFile;
use App\Models\User;
use App\Modules\Media\Support\MediaAccess;
use App\Modules\Media\Support\MediaOptions;
use App\Modules\Shared\PortfolioScope;
use App\Modules\Shared\ResourcePresenter;

class MediaFileFormPresenter
{
    public function __construct(
        private readonly MediaAccess $access,
        private readonly PortfolioScope $portfolios,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $actor, ?MediaFile $mediaFile = null): array
    {
        $mediaFile
            ? $this->access->ensureCanManage($actor, $mediaFile)
            : $this->access->ensureManager($actor);
        $fields = [];

        if ($actor->hasRole('superadmin')) {
            $portfolioOptions = [[
                'value' => '',
                'label' => $this->translated('app.media.global_website'),
            ]];

            foreach ($this->portfolios->options($actor) as $portfolio) {
                $portfolioOptions[] = [
                    'value' => $portfolio['id'],
                    'label' => $portfolio['name'],
                ];
            }

            $fields[] = [
                'name' => 'portfolio_id',
                'label' => trans('app.media.portfolio'),
                'type' => 'select',
                'options' => $portfolioOptions,
            ];
        }

        $fields = [
            ...$fields,
            ['name' => 'collection', 'label' => trans('app.media.collection'), 'required' => true, 'help' => trans('app.media.collection_help')],
            ['name' => 'visibility', 'label' => trans('app.media.visibility'), 'type' => 'select', 'required' => true, 'options' => collect(MediaOptions::VISIBILITIES)->map(fn (string $value): array => ['value' => $value, 'label' => trans("app.media.{$value}")])->all(), 'help' => trans('app.media.visibility_help')],
            ['name' => 'title_en', 'label' => trans('app.media.title_en'), 'required' => true],
            ['name' => 'title_ar', 'label' => trans('app.media.title_ar'), 'required' => true],
            ['name' => 'alt_text_en', 'label' => trans('app.media.alt_text_en'), 'required' => true, 'help' => trans('app.media.alt_text_help')],
            ['name' => 'alt_text_ar', 'label' => trans('app.media.alt_text_ar'), 'required' => true, 'help' => trans('app.media.alt_text_help')],
        ];

        if ($mediaFile === null) {
            $fields[] = [
                'name' => 'file',
                'label' => trans('app.media.image_file'),
                'type' => 'file',
                'required' => true,
                'accept' => '.jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif',
                'help' => trans('app.media.image_help'),
            ];
        }

        $fields = $this->resources->sectionFields($fields, [
            trans('app.media.scope_section') => [
                'description' => trans('app.media.scope_help'),
                'fields' => ['portfolio_id', 'collection', 'visibility'],
            ],
            trans('app.media.accessibility_section') => [
                'description' => trans('app.media.accessibility_help'),
                'fields' => ['title_en', 'title_ar', 'alt_text_en', 'alt_text_ar'],
            ],
            trans('app.media.file_section') => [
                'description' => trans('app.media.file_section_help'),
                'fields' => ['file'],
            ],
        ]);

        $initialValues = $mediaFile
            ? [
                'portfolio_id' => (string) ($mediaFile->portfolio_id ?? ''),
                'collection' => $mediaFile->collection,
                'visibility' => $mediaFile->visibility,
                'title_en' => $mediaFile->title_en ?? '',
                'title_ar' => $mediaFile->title_ar ?? '',
                'alt_text_en' => $mediaFile->alt_text_en ?? '',
                'alt_text_ar' => $mediaFile->alt_text_ar ?? '',
                'file' => null,
            ]
            : [
                'portfolio_id' => (string) request('portfolio_id', $actor->portfolio_id ?? ''),
                'collection' => 'default',
                'visibility' => 'public',
                'title_en' => '',
                'title_ar' => '',
                'alt_text_en' => '',
                'alt_text_ar' => '',
                'file' => null,
            ];

        return [
            'title' => $mediaFile ? trans('app.media.edit_media') : trans('app.media.upload_media'),
            'description' => $mediaFile ? trans('app.media.edit_description') : trans('app.media.upload_description'),
            'backHref' => $mediaFile ? route('media-files.show', $mediaFile) : route('media-files.index'),
            'backLabel' => $mediaFile ? trans('app.media.media_detail') : trans('app.media.all_media'),
            'action' => $mediaFile ? route('media-files.update', $mediaFile) : route('media-files.store'),
            'method' => $mediaFile ? 'put' : 'post',
            'submitLabel' => $mediaFile ? trans('app.media.update_media') : trans('app.media.upload_media'),
            'fields' => $fields,
            'initialValues' => $initialValues,
        ];
    }

    private function translated(string $key): string
    {
        $value = trans($key);

        if (! is_string($value)) {
            throw new \LogicException("Expected a string translation for {$key}.");
        }

        return $value;
    }
}
