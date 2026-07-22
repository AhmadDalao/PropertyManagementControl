<?php

namespace App\Modules\Documents\Requests;

use App\Modules\Documents\Support\DocumentAccess;
use App\Modules\Documents\Support\DocumentRules;
use Illuminate\Foundation\Http\FormRequest;

final class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor !== null && app(DocumentAccess::class)->canManageSection($actor);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return DocumentRules::create();
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
