<?php

namespace App\Modules\Media\Support;

use Illuminate\Validation\Rule;

final class MediaRules
{
    /** @return array<string, array<int, mixed>> */
    public static function create(): array
    {
        return [
            ...self::metadata(),
            'file' => [
                'required',
                'file',
                'extensions:'.implode(',', MediaOptions::EXTENSIONS),
                'mimes:'.implode(',', MediaOptions::EXTENSIONS),
                'mimetypes:'.implode(',', MediaOptions::MIME_TYPES),
                'max:'.MediaOptions::MAX_FILE_KILOBYTES,
                'dimensions:max_width='.MediaOptions::MAX_DIMENSION.',max_height='.MediaOptions::MAX_DIMENSION,
            ],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    public static function update(): array
    {
        return self::metadata();
    }

    /** @return array<string, string> */
    public static function attributes(): array
    {
        return [
            'portfolio_id' => trans('app.media.validation_attributes.portfolio_id'),
            'collection' => trans('app.media.validation_attributes.collection'),
            'title_en' => trans('app.media.validation_attributes.title_en'),
            'title_ar' => trans('app.media.validation_attributes.title_ar'),
            'alt_text_en' => trans('app.media.validation_attributes.alt_text_en'),
            'alt_text_ar' => trans('app.media.validation_attributes.alt_text_ar'),
            'visibility' => trans('app.media.validation_attributes.visibility'),
            'file' => trans('app.media.validation_attributes.file'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        if (($data['portfolio_id'] ?? null) === '') {
            $data['portfolio_id'] = null;
        }

        foreach (['collection', 'title_en', 'title_ar', 'alt_text_en', 'alt_text_ar', 'visibility'] as $field) {
            if (is_string($data[$field] ?? null)) {
                $data[$field] = trim($data[$field]);
            }
        }

        if (($data['collection'] ?? null) === '') {
            $data['collection'] = 'default';
        }

        return $data;
    }

    /** @return array<string, array<int, mixed>> */
    private static function metadata(): array
    {
        return [
            'portfolio_id' => ['nullable', 'integer', 'min:1'],
            'collection' => ['required', 'string', 'max:80'],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'alt_text_en' => ['required', 'string', 'max:255'],
            'alt_text_ar' => ['required', 'string', 'max:255'],
            'visibility' => ['required', 'string', Rule::in(MediaOptions::VISIBILITIES)],
        ];
    }

    private function __construct() {}
}
