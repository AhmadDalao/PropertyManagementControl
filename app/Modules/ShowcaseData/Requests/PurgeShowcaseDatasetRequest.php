<?php

namespace App\Modules\ShowcaseData\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurgeShowcaseDatasetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('superadmin') ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'confirmation' => [
                'required',
                'string',
                'max:100',
                Rule::in([trans('app.showcase.confirmation')]),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'confirmation.in' => trans('app.showcase.purge_confirmation_invalid'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'confirmation' => trim((string) $this->input('confirmation')),
        ]);
    }
}
