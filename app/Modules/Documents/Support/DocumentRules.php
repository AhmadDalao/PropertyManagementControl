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
            ...self::metadata(DocumentOptions::UPLOAD_TYPES),
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
        return self::metadata(DocumentOptions::TYPES);
    }

    /** @return array<string, array<int, mixed>> */
    public static function paymentProof(): array
    {
        return [
            'proof' => [
                'required',
                'file',
                'extensions:pdf',
                'mimes:pdf',
                'mimetypes:application/pdf',
                'max:10240',
                new ValidPdf,
            ],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    public static function paymentProofReview(): array
    {
        return [
            'status' => ['required', Rule::in(['accepted', 'rejected'])],
            'review_note' => [
                'nullable',
                'string',
                'max:1000',
                'required_if:status,rejected',
            ],
        ];
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
            'issued_on' => trans('app.documents.issued_on'),
            'expires_on' => trans('app.documents.expires_on'),
            'is_public' => trans('app.documents.tenant_portal'),
            'file' => trans('app.documents.pdf_file'),
            'proof' => trans('app.payments.payment_proof_pdf'),
            'note' => trans('app.payments.submission_note'),
            'review_note' => trans('app.payments.review_note'),
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

        foreach (['issued_on', 'expires_on'] as $field) {
            $data[$field] = trim((string) ($data[$field] ?? '')) ?: null;
        }

        return $data;
    }

    /**
     * @param  array<int, string>  $types
     * @return array<string, array<int, mixed>>
     */
    private static function metadata(array $types): array
    {
        return [
            'type' => ['required', 'string', Rule::in($types)],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'issued_on' => ['nullable', 'date_format:Y-m-d'],
            'expires_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:issued_on'],
            'is_public' => ['nullable', 'boolean'],
        ];
    }

    private function __construct() {}
}
