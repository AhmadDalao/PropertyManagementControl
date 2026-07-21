<?php

namespace App\Modules\Wording\Requests;

use App\Modules\Wording\TranslationCompletenessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WordingIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('superadmin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => trim((string) $this->query('search', '')),
            'group' => trim((string) $this->query('group', 'all')),
            'state' => trim((string) $this->query('state', 'all')),
            'per_page' => (int) $this->query('per_page', 25),
            'page' => (int) $this->query('page', 1),
            'content_module' => trim((string) $this->query('content_module', 'all')),
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:500'],
            'group' => ['required', 'string', 'max:100'],
            'state' => ['required', Rule::in(['all', 'customized', 'default'])],
            'per_page' => ['required', 'integer', Rule::in([10, 25, 50, 100])],
            'page' => ['required', 'integer', 'min:1'],
            'content_module' => [
                'required',
                Rule::in(['all', ...TranslationCompletenessService::MODULES]),
            ],
        ];
    }

    /**
     * @return array{search:string,group:string,state:string,per_page:int,page:int,content_module:string}
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'search' => (string) ($validated['search'] ?? ''),
            'group' => (string) $validated['group'],
            'state' => (string) $validated['state'],
            'per_page' => (int) $validated['per_page'],
            'page' => (int) $validated['page'],
            'content_module' => (string) $validated['content_module'],
        ];
    }
}
