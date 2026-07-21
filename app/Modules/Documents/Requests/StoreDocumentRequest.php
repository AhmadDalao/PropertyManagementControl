<?php

namespace App\Modules\Documents\Requests;

use App\Modules\Documents\Support\DocumentOptions;
use App\Modules\Shared\Rules\ValidPdf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['superadmin', 'owner', 'property_manager']) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'documentable_type' => ['required', 'string', Rule::in(DocumentOptions::ATTACHMENTS)],
            'documentable_id' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'string', Rule::in(DocumentOptions::TYPES)],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'is_public' => ['nullable', 'boolean'],
            'file' => [
                'required',
                'file',
                'extensions:pdf',
                'mimes:pdf',
                'mimetypes:application/pdf',
                'max:10240',
                new ValidPdf,
            ],
        ];
    }
}
