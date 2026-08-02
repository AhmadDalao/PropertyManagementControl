<?php

namespace App\Modules\OpeningData\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CommitOpeningDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'preview_token' => ['required', 'string', 'size:48', 'regex:/^[A-Za-z0-9]+$/'],
            'confirmed' => ['accepted'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'preview_token' => trans('app.opening_data.preview'),
            'confirmed' => trans('app.opening_data.confirmation'),
        ];
    }
}
