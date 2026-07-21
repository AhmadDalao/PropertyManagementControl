<?php

namespace App\Modules\Documents\Requests;

use App\Models\Document;
use App\Modules\Documents\Support\DocumentOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $document = $this->route('document');

        return $actor !== null
            && $document instanceof Document
            && $actor->hasAnyRole(['superadmin', 'owner', 'property_manager'])
            && ($actor->hasRole('superadmin') || $actor->portfolio_id === $document->portfolio_id);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(DocumentOptions::TYPES)],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'is_public' => ['nullable', 'boolean'],
        ];
    }
}
