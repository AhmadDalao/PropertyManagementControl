<?php

namespace App\Modules\SystemReadiness\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReadinessIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('superadmin') ?? false;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'portfolio_id' => ['nullable', 'integer', 'exists:portfolios,id'],
        ];
    }

    public function portfolioId(): ?int
    {
        $value = $this->validated('portfolio_id');

        return is_numeric($value) ? (int) $value : null;
    }
}
