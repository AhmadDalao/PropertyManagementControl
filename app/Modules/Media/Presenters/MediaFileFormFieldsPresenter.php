<?php

namespace App\Modules\Media\Presenters;

use App\Modules\Media\Data\MediaFileFormData;
use App\Modules\Media\Support\MediaOptions;
use App\Modules\Shared\ResourcePresenter;

final class MediaFileFormFieldsPresenter
{
    public function __construct(private readonly ResourcePresenter $resources) {}

    /** @return array<int, array<string, mixed>> */
    public function present(MediaFileFormData $data): array
    {
        $fields = $data->actor->hasRole('superadmin') ? [$this->portfolioField($data)] : [];
        $fields = [
            ...$fields,
            ['name' => 'collection', 'label' => trans('app.media.collection'), 'required' => true, 'help' => trans('app.media.collection_help')],
            ['name' => 'visibility', 'label' => trans('app.media.visibility'), 'type' => 'select', 'required' => true, 'options' => $this->visibilityOptions(), 'help' => trans('app.media.visibility_help')],
            ['name' => 'title_en', 'label' => trans('app.media.title_en'), 'required' => true],
            ['name' => 'title_ar', 'label' => trans('app.media.title_ar'), 'required' => true],
            ['name' => 'alt_text_en', 'label' => trans('app.media.alt_text_en'), 'required' => true, 'help' => trans('app.media.alt_text_help')],
            ['name' => 'alt_text_ar', 'label' => trans('app.media.alt_text_ar'), 'required' => true, 'help' => trans('app.media.alt_text_help')],
        ];

        if ($data->mediaFile === null) {
            $fields[] = [
                'name' => 'file',
                'label' => trans('app.media.image_file'),
                'type' => 'file',
                'required' => true,
                'accept' => '.jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif',
                'help' => trans('app.media.image_help'),
            ];
        }

        return $this->resources->sectionFields($fields, [
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
    }

    /** @return array<string, mixed> */
    private function portfolioField(MediaFileFormData $data): array
    {
        return [
            'name' => 'portfolio_id',
            'label' => trans('app.media.portfolio'),
            'type' => 'select',
            'options' => [
                ['value' => '', 'label' => trans('app.media.global_website')],
                ...array_map(fn (array $portfolio): array => [
                    'value' => $portfolio['id'],
                    'label' => $portfolio['name'],
                ], $data->portfolioOptions),
            ],
        ];
    }

    /** @return array<int, array{value:string,label:string}> */
    private function visibilityOptions(): array
    {
        return array_map(fn (string $visibility): array => [
            'value' => $visibility,
            'label' => MediaOptions::label($visibility),
        ], MediaOptions::VISIBILITIES);
    }
}
