<?php

namespace App\Modules\Media\Requests;

use App\Modules\Media\Support\MediaOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMediaFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['superadmin', 'owner', 'property_manager']) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'portfolio_id' => ['nullable', 'integer', 'exists:portfolios,id'],
            'collection' => ['required', 'string', 'max:80'],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'alt_text_en' => ['required', 'string', 'max:255'],
            'alt_text_ar' => ['required', 'string', 'max:255'],
            'visibility' => ['required', 'string', Rule::in(MediaOptions::VISIBILITIES)],
            'file' => [
                'required',
                'file',
                'max:'.MediaOptions::MAX_FILE_KILOBYTES,
                'mimes:jpg,jpeg,png,webp,gif',
                'mimetypes:'.implode(',', MediaOptions::MIME_TYPES),
                'dimensions:max_width=10000,max_height=10000',
            ],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'collection' => trim((string) ($this->input('collection') ?: 'default')),
        ]);
    }
}
