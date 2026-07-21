<?php

namespace App\Modules\Portfolios\Requests;

use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Portfolios\Support\PortfolioAccess;
use App\Modules\Portfolios\Support\PortfolioOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePortfolioRequest extends FormRequest
{
    use HasPortfolioValidationAttributes;

    public function authorize(): bool
    {
        $actor = $this->user();
        $portfolio = $this->route('portfolio');

        return $actor instanceof User
            && $portfolio instanceof Portfolio
            && app(PortfolioAccess::class)->canUpdate($actor, $portfolio);
    }

    protected function prepareForValidation(): void
    {
        $portfolio = $this->route('portfolio');
        $fallback = $portfolio instanceof Portfolio && is_array($portfolio->module_settings)
            ? $portfolio->module_settings
            : null;

        $this->preparePortfolioInput($fallback);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $actor = $this->user();
        $portfolio = $this->route('portfolio');
        $statuses = $actor instanceof User && $portfolio instanceof Portfolio
            ? PortfolioOptions::updateStatuses($actor, $portfolio)
            : [];

        return [
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'address_ar' => ['nullable', 'string', 'max:2000'],
            'default_currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'status' => ['required', Rule::in($statuses)],
            'module_settings' => ['required', 'array:'.implode(',', PortfolioOptions::moduleKeys())],
            'module_settings.*' => ['boolean'],
        ];
    }
}
