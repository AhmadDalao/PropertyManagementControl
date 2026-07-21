<?php

namespace App\Modules\Portfolios\Requests;

use App\Modules\Portfolios\Support\PortfolioOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePortfolioRequest extends FormRequest
{
    use HasPortfolioValidationAttributes;

    public function authorize(): bool
    {
        return $this->user()?->hasRole('superadmin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->preparePortfolioInput();

        if (blank($this->input('country'))) {
            $this->merge(['country' => 'Saudi Arabia']);
        }
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'alpha_dash:ascii', 'unique:portfolios,code'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'address_ar' => ['nullable', 'string', 'max:2000'],
            'default_currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'status' => ['required', Rule::in(PortfolioOptions::CREATION_STATUSES)],
            'module_settings' => ['required', 'array:'.implode(',', PortfolioOptions::moduleKeys())],
            'module_settings.*' => ['boolean'],
        ];
    }
}
