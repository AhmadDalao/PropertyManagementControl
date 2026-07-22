<?php

namespace App\Modules\Media\Presenters;

use App\Models\MediaFile;
use App\Models\User;
use App\Modules\Media\Queries\MediaFileFormDataQuery;

final class MediaFileFormPresenter
{
    public function __construct(
        private readonly MediaFileFormDataQuery $forms,
        private readonly MediaFileFormFieldsPresenter $fields,
        private readonly MediaFileFormValuesPresenter $values,
    ) {}

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    public function present(User $actor, ?MediaFile $mediaFile = null, array $defaults = []): array
    {
        $data = $this->forms->get($actor, $mediaFile, $defaults);

        return [
            'title' => $mediaFile ? trans('app.media.edit_media') : trans('app.media.upload_media'),
            'description' => $mediaFile ? trans('app.media.edit_description') : trans('app.media.upload_description'),
            'backHref' => $mediaFile ? route('media-files.show', $mediaFile) : route('media-files.index'),
            'backLabel' => $mediaFile ? trans('app.media.media_detail') : trans('app.media.all_media'),
            'action' => $mediaFile ? route('media-files.update', $mediaFile) : route('media-files.store'),
            'method' => $mediaFile ? 'put' : 'post',
            'submitLabel' => $mediaFile ? trans('app.media.update_media') : trans('app.media.upload_media'),
            'fields' => $this->fields->present($data),
            'initialValues' => $this->values->present($data),
        ];
    }
}
