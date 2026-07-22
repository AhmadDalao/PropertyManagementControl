<?php

namespace App\Modules\Assets\Requests;

use App\Modules\Assets\Support\AssetOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    use HasAssetValidationAttributes;

    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['superadmin', 'owner', 'property_manager']) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'portfolio_id' => [
                Rule::requiredIf($this->user()?->hasRole('superadmin') ?? false),
                'nullable',
                'integer',
                'exists:portfolios,id',
            ],
            'code' => ['nullable', 'string', 'max:50', 'unique:assets,code'],
            ...$this->assetRules(AssetOptions::MUTABLE_STATUSES),
        ];
    }
}
