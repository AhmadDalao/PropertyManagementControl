<?php

namespace App\Modules\Documents\Support;

use App\Modules\Shared\Rules\ValidPdf;
use Illuminate\Validation\Rule;

final class DocumentRules
{
    /** @return array<string, array<int, mixed>> */
    public static function create(): array
    {
        return [
            'documentable_type' => ['required', 'string', Rule::in(DocumentOptions::ATTACHMENTS)],
            'documentable_id' => ['required', 'integer', 'min:1'],
            ...self::metadata(),
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

    /** @return array<string, array<int, mixed>> */
    public static function update(): array
    {
        return self::metadata();
    }

    /** @return array<string, string> */
    public static function attributes(): array
    {
        return [
            'documentable_type' => trans('app.documents.attach_to'),
            'documentable_id' => trans('app.documents.attached_record_id'),
            'type' => trans('app.documents.document_type'),
            'title_en' => trans('app.documents.english_title'),
            'title_ar' => trans('app.documents.arabic_title'),
            'is_public' => trans('app.documents.tenant_portal'),
            'file' => trans('app.documents.pdf_file'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        foreach (['documentable_type', 'type', 'title_en', 'title_ar'] as $field) {
            if (is_string($data[$field] ?? null)) {
                $data[$field] = trim($data[$field]);
            }
        }

        return $data;
    }

    /** @return array<string, array<int, mixed>> */
    private static function metadata(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(DocumentOptions::TYPES)],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'is_public' => ['nullable', 'boolean'],
        ];
    }

    private function __construct() {}
}
