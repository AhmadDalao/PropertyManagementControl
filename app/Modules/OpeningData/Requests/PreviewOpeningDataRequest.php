<?php

namespace App\Modules\OpeningData\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PreviewOpeningDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'portfolio_id' => ['required', 'integer', 'min:1'],
            'file' => ['required', 'file', 'extensions:xlsx', 'max:10240'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'portfolio_id' => trans('app.opening_data.portfolio'),
            'file' => trans('app.opening_data.workbook'),
        ];
    }
}
