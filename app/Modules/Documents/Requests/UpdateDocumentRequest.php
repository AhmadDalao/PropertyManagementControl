<?php

namespace App\Modules\Documents\Requests;

use App\Models\Document;
use App\Modules\Documents\Support\DocumentAccess;
use App\Modules\Documents\Support\DocumentRules;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $document = $this->route('document');

        return $actor !== null
            && $document instanceof Document
            && app(DocumentAccess::class)->canManage($actor, $document);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return DocumentRules::update();
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return DocumentRules::attributes();
    }

    protected function prepareForValidation(): void
    {
        $this->replace(DocumentRules::normalize($this->all()));
    }
}
