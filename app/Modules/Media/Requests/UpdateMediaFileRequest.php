<?php

namespace App\Modules\Media\Requests;

use App\Models\MediaFile;
use App\Modules\Media\Support\MediaOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMediaFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $mediaFile = $this->route('mediaFile');

        if ($actor?->hasRole('superadmin')) {
            return true;
        }

        return $actor?->hasAnyRole(['owner', 'property_manager']) === true
            && $mediaFile instanceof MediaFile
            && $mediaFile->portfolio_id !== null
            && $mediaFile->portfolio_id === $actor->portfolio_id;
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
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'collection' => trim((string) ($this->input('collection') ?: 'default')),
        ]);
    }
}
